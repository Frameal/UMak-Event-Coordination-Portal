<?php
// File: api/org_create_event.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $eventName = $_POST['event_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $eventDate = $_POST['event_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    
    // New Registration Times
    $regStart = !empty($_POST['registration_start']) ? $_POST['registration_start'] : NULL;
    $regEnd = !empty($_POST['registration_end']) ? $_POST['registration_end'] : NULL;

    $venueId = $_POST['venue_id'] ?? '';
    $capacity = $_POST['attendees_capacity'] ?? 0;
    $orgId = $_POST['created_by_org'] ?? null;
    $targetCollege = $_POST['target_college'] ?? 'All';
    $targetYear = $_POST['target_year_level'] ?? 'All';
    $eventType = $_POST['event_type'] ?? 'Other';
    $status = $_POST['status'] ?? 'Pending Approval';
    $rentalFee = !empty($_POST['rental_fee']) ? $_POST['rental_fee'] : NULL;
    
    // Requirements
    $reqSound = isset($_POST['req_sound']) ? 1 : 0;
    $reqProjector = isset($_POST['req_projector']) ? 1 : 0;
    $reqChairs = !empty($_POST['req_chairs_qty']) ? $_POST['req_chairs_qty'] : 0;
    $reqTables = !empty($_POST['req_tables_qty']) ? $_POST['req_tables_qty'] : 0;
    $reqInternet = isset($_POST['req_internet']) ? 1 : 0;
    $reqParking = isset($_POST['req_parking']) ? 1 : 0;
    $reqMedical = isset($_POST['req_medical']) ? 1 : 0;
    $reqCleaning = isset($_POST['req_cleaning']) ? 1 : 0;
    $reqFood = isset($_POST['req_food']) ? 1 : 0;
    $reqRemarks = $_POST['req_remarks'] ?? '';

    if (empty($eventName) || empty($eventDate) || empty($venueId) || empty($orgId)) {
        echo json_encode(["success" => false, "message" => "Required fields missing."]);
        exit();
    }

    // Handle Image
    $bannerPath = null;
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/events/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('evt_') . '.' . $ext;
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $targetFile)) {
            $bannerPath = '../../images/events/' . $filename; 
        }
    }

    try {
        $db->beginTransaction();

        // 1. Insert Event
        $sqlEvent = "INSERT INTO events 
            (event_name, description, event_date, start_time, end_time, venue_id, 
             attendees_capacity, target_college, target_year_level, event_type, 
             status, banner_image, created_by_org, rental_fee, approval_step,
             registration_start, registration_end)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";
        
        $stmtEvent = $db->prepare($sqlEvent);
        $stmtEvent->execute([
            $eventName, $description, $eventDate, $startTime, $endTime, $venueId,
            $capacity, $targetCollege, $targetYear, $eventType, 
            $status, $bannerPath, $orgId, $rentalFee, $regStart, $regEnd
        ]);
        
        $eventId = $db->lastInsertId();

        // 2. Insert Requirements
        $sqlReq = "INSERT INTO event_requirements 
            (event_id, needs_sound_system, needs_projector, needs_chairs, needs_tables, 
             needs_internet, needs_parking, needs_medical, needs_cleaning, has_food, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
        $stmtReq = $db->prepare($sqlReq);
        $stmtReq->execute([
            $eventId, $reqSound, $reqProjector, $reqChairs, $reqTables,
            $reqInternet, $reqParking, $reqMedical, $reqCleaning, $reqFood, $reqRemarks
        ]);

        // 3. Initialize Approvals (CSOA/UFMO + Conditionals)
        requireApproval($db, $eventId, 'CSOA');
        requireApproval($db, $eventId, 'UFMO');

        if ($reqInternet || $reqProjector) requireApproval($db, $eventId, 'CIT');
        if ($reqSound || $reqCleaning) requireApproval($db, $eventId, 'GSO');
        if ($reqChairs > 0 || $reqTables > 0) requireApproval($db, $eventId, 'SPMO');
        if ($reqMedical || $reqParking || $reqFood) requireApproval($db, $eventId, 'OHSO');
        if ($rentalFee > 0) requireApproval($db, $eventId, 'Accounting');

        $db->commit();
        echo json_encode(["success" => true, "message" => "Event created successfully!"]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}

function requireApproval($db, $eventId, $dept) {
    $stmt = $db->prepare("INSERT INTO event_approvals (event_id, department, status) VALUES (?, ?, 'Pending')");
    $stmt->execute([$eventId, $dept]);
}
?>