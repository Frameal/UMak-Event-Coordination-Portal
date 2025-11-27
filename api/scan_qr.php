<?php
// File: umak_ecp/api/scan_qr.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['qr_code']) || empty($data['event_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid Scan Data."]);
    exit();
}

$qrCode = $data['qr_code'];
$eventId = $data['event_id'];
$orgId = $data['org_id'] ?? null;

try {
    // 1. Verify that the Organization owns this event (Security Check)
    if ($orgId) {
        $stmtEvt = $db->prepare("SELECT event_id FROM events WHERE event_id = ? AND created_by_org = ?");
        $stmtEvt->execute([$eventId, $orgId]);
        if ($stmtEvt->rowCount() == 0) {
            echo json_encode(["success" => false, "message" => "Error: This event does not belong to your organization."]);
            exit();
        }
    }

    // 2. Find the Registration based on QR Code and Event ID
    // We join with the students table to get student details for the display card
    $query = "SELECT r.registration_id, r.status, r.student_id, 
                     s.firstname, s.lastname, s.student_number, s.college, s.year_level, s.section, s.course
              FROM event_registrations r
              JOIN students s ON r.student_id = s.student_id
              WHERE r.qr_code = ? AND r.event_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$qrCode, $eventId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registration) {
        echo json_encode(["success" => false, "message" => "Invalid QR Code or Student is not registered for this specific event."]);
        exit();
    }

    // 3. Check Current Status
    if ($registration['status'] === 'Attended') {
        echo json_encode([
            "success" => false, 
            "message" => "Student has already been scanned for this event.",
            "student" => $registration // Return data anyway so the scanner shows who it is
        ]);
        exit();
    }

    if ($registration['status'] === 'Cancelled') {
        echo json_encode(["success" => false, "message" => "This registration was cancelled."]);
        exit();
    }

    // 4. Mark as Attended
    $updateSql = "UPDATE event_registrations SET status = 'Attended', attended_at = NOW() WHERE registration_id = ?";
    $updateStmt = $db->prepare($updateSql);
    
    if ($updateStmt->execute([$registration['registration_id']])) {
        // Optionally update the main event counter if your system relies on it
        // $db->prepare("UPDATE events SET current_attendees = current_attendees + 1 WHERE event_id = ?")->execute([$eventId]);
        
        echo json_encode([
            "success" => true,
            "message" => "Attendance Confirmed",
            "student" => $registration
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Database update failed."]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}
?>