<?php
// File: umak_ecp/api/org_update_event.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid Request Method"]);
    exit();
}

$eventId = $_POST['event_id'] ?? null;
$orgId = $_POST['org_id'] ?? null;

if (!$eventId || !$orgId) {
    echo json_encode(["success" => false, "message" => "Missing Event ID or Org ID"]);
    exit();
}

try {
    // 1. Verify Ownership
    $check = $db->prepare("SELECT event_id FROM events WHERE event_id = ? AND created_by_org = ?");
    $check->execute([$eventId, $orgId]);
    if ($check->rowCount() == 0) {
        echo json_encode(["success" => false, "message" => "Access Denied"]);
        exit();
    }

    $db->beginTransaction();

    // 2. Update Basic Event Info
    $eventName = $_POST['event_name'];
    $description = $_POST['description'];
    $eventDate = $_POST['event_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $regStart = !empty($_POST['registration_start']) ? $_POST['registration_start'] : NULL;
    $regEnd = !empty($_POST['registration_end']) ? $_POST['registration_end'] : NULL;
    $venueId = $_POST['venue_id'];
    $capacity = $_POST['attendees_capacity'];
    $targetCollege = $_POST['target_college'];
    $targetYear = $_POST['target_year_level'];
    $eventType = $_POST['event_type'];
    
    // Status handling
    $status = $_POST['status'];
    if ($status === 'Draft' || $status === 'To be Approved') {
        $status = 'Pending Approval'; 
    }
    
    $reqEval = isset($_POST['requires_evaluation']) ? 1 : 0;
    $rentalFee = !empty($_POST['rental_fee']) ? $_POST['rental_fee'] : NULL;

    $sql = "UPDATE events SET 
            event_name=?, description=?, event_date=?, start_time=?, end_time=?, 
            venue_id=?, attendees_capacity=?, target_college=?, target_year_level=?, 
            event_type=?, status=?, requires_evaluation=?, rental_fee=?, approval_step=1,
            registration_start=?, registration_end=?";
    
    $params = [
        $eventName, $description, $eventDate, $startTime, $endTime, 
        $venueId, $capacity, $targetCollege, $targetYear, 
        $eventType, $status, $reqEval, $rentalFee, $regStart, $regEnd
    ];

    // Handle Image Upload
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/events/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('evt_') . '.' . $ext;
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $targetFile)) {
            $bannerPath = '../../images/events/' . $filename;
            $sql .= ", banner_image=?";
            $params[] = $bannerPath;
        }
    }

    $sql .= " WHERE event_id=?";
    $params[] = $eventId;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // 3. Update Requirements
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

    // Upsert Requirements
    $checkReq = $db->prepare("SELECT req_id FROM event_requirements WHERE event_id = ?");
    $checkReq->execute([$eventId]);
    if ($checkReq->rowCount() > 0) {
        $sqlReq = "UPDATE event_requirements SET 
            needs_sound_system=?, needs_projector=?, needs_chairs=?, needs_tables=?, 
            needs_internet=?, needs_parking=?, needs_medical=?, needs_cleaning=?, 
            has_food=?, remarks=? 
            WHERE event_id=?";
        $db->prepare($sqlReq)->execute([
            $reqSound, $reqProjector, $reqChairs, $reqTables, $reqInternet,
            $reqParking, $reqMedical, $reqCleaning, $reqFood, $reqRemarks, $eventId
        ]);
    } else {
        $sqlReq = "INSERT INTO event_requirements 
            (event_id, needs_sound_system, needs_projector, needs_chairs, needs_tables, 
             needs_internet, needs_parking, needs_medical, needs_cleaning, has_food, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db->prepare($sqlReq)->execute([
            $eventId, $reqSound, $reqProjector, $reqChairs, $reqTables, $reqInternet,
            $reqParking, $reqMedical, $reqCleaning, $reqFood, $reqRemarks
        ]);
    }

    // 4. MANAGE APPROVALS
    function requireApproval($db, $eventId, $dept) {
        $stmt = $db->prepare("INSERT INTO event_approvals (event_id, department, status) 
                              VALUES (?, ?, 'Pending') 
                              ON DUPLICATE KEY UPDATE status = 'Pending'");
        $stmt->execute([$eventId, $dept]);
    }

    function removeApproval($db, $eventId, $dept) {
        $stmt = $db->prepare("DELETE FROM event_approvals WHERE event_id = ? AND department = ?");
        $stmt->execute([$eventId, $dept]);
    }

    requireApproval($db, $eventId, 'CSOA');
    requireApproval($db, $eventId, 'UFMO');

    if ($reqInternet || $reqProjector) requireApproval($db, $eventId, 'CIT'); 
    else removeApproval($db, $eventId, 'CIT');

    if ($reqSound || $reqCleaning) requireApproval($db, $eventId, 'GSO');
    else removeApproval($db, $eventId, 'GSO');

    if ($reqChairs > 0 || $reqTables > 0) requireApproval($db, $eventId, 'SPMO');
    else removeApproval($db, $eventId, 'SPMO');

    if ($reqMedical || $reqParking || $reqFood) requireApproval($db, $eventId, 'OHSO');
    else removeApproval($db, $eventId, 'OHSO');

    if ($rentalFee > 0) requireApproval($db, $eventId, 'Accounting');
    else removeApproval($db, $eventId, 'Accounting');

    $db->commit();
    echo json_encode(["success" => true, "message" => "Event updated and resubmitted for approval."]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>