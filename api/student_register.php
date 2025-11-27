<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Set Timezone
date_default_timezone_set('Asia/Manila');

include_once '../config/database.php';
include_once 'update_status_helper.php'; 

$database = new Database();
$db = $database->getConnection();

// Force status update before processing
updateEventStatuses($db);

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->student_id) || !isset($data->event_id)) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit();
}

try {
    // 1. Check for pending evaluations
    $checkEvalSql = "SELECT COUNT(*) as pending_count 
                     FROM event_registrations er
                     JOIN events e ON er.event_id = e.event_id
                     WHERE er.student_id = ? 
                     AND er.status = 'Attended' 
                     AND er.has_evaluated = 0 
                     AND e.requires_evaluation = 1";
    
    $stmtCheck = $db->prepare($checkEvalSql);
    $stmtCheck->execute([$data->student_id]);
    $pendingResult = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($pendingResult['pending_count'] > 0) {
        echo json_encode([
            "success" => false, 
            "message" => "You have pending evaluations. You must complete them before registering for new events."
        ]);
        exit();
    }

    // 2. Check if already registered
    $checkReg = "SELECT registration_id FROM event_registrations WHERE student_id = ? AND event_id = ? AND status != 'Cancelled'";
    $stmtReg = $db->prepare($checkReg);
    $stmtReg->execute([$data->student_id, $data->event_id]);
    
    if($stmtReg->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "Already registered."]);
        exit();
    }

    // 3. Fetch Event Details
    $evtSql = "SELECT attendees_capacity, current_attendees, status, registration_start, registration_end FROM events WHERE event_id = ?";
    $stmtEvt = $db->prepare($evtSql);
    $stmtEvt->execute([$data->event_id]);
    $event = $stmtEvt->fetch(PDO::FETCH_ASSOC);

    // 4. Status & Time Validation
    // Even if status isn't updated yet, check the raw time
    $now = date('Y-m-d H:i:s');
    
    $isOpen = false;
    
    if ($event['status'] === 'Registration Open') {
        $isOpen = true;
    } 
    // Fallback: If status is 'Published' but time has passed, allow it (double check)
    elseif ($event['status'] === 'Published' && $event['registration_start'] && $event['registration_start'] <= $now) {
        $isOpen = true;
        // Hotfix status
        $db->prepare("UPDATE events SET status = 'Registration Open' WHERE event_id = ?")->execute([$data->event_id]);
    }

    if (!$isOpen) {
        $msg = "Registration is not open.";
        if($event['status'] === 'Published' && $event['registration_start']) {
            $msg = "Registration opens at " . date('h:i A', strtotime($event['registration_start']));
        } elseif ($event['status'] === 'Registration Closed') {
            $msg = "Registration has closed.";
        }
        echo json_encode(["success" => false, "message" => $msg]);
        exit();
    }

    // 5. Capacity Check
    if($event['current_attendees'] >= $event['attendees_capacity']){
        echo json_encode(["success" => false, "message" => "Event is full."]);
        exit();
    }

    // 6. Generate QR and Register
    $stuSql = "SELECT student_number FROM students WHERE student_id = ?";
    $stmtStu = $db->prepare($stuSql);
    $stmtStu->execute([$data->student_id]);
    $student = $stmtStu->fetch(PDO::FETCH_ASSOC);
    
    $qr_code = "QR-" . $student['student_number'] . "-E" . $data->event_id . "-" . time();

    $insertSql = "INSERT INTO event_registrations (event_id, student_id, qr_code, status, registration_date) VALUES (?, ?, ?, 'Registered', NOW())";
    
    if($db->prepare($insertSql)->execute([$data->event_id, $data->student_id, $qr_code])) {
        $db->prepare("UPDATE events SET current_attendees = current_attendees + 1 WHERE event_id = ?")->execute([$data->event_id]);
        
        echo json_encode([
            "success" => true, 
            "message" => "Registration successful!", 
            "qr_code" => $qr_code 
        ]);
    } else {
        throw new Exception("Registration failed.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>