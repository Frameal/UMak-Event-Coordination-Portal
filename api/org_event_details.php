<?php
// File: umak_ecp/api/org_event_details.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$eventId = isset($_GET['event_id']) ? $_GET['event_id'] : null;
$orgId = isset($_GET['org_id']) ? $_GET['org_id'] : null;

if (!$eventId || !$orgId) {
    echo json_encode(["success" => false, "message" => "Event ID and Org ID required"]);
    exit();
}

try {
    // 1. Fetch Event Details (Verify ownership)
    $query = "SELECT e.*, v.venue_name, v.location 
              FROM events e 
              LEFT JOIN venues v ON e.venue_id = v.venue_id 
              WHERE e.event_id = ? AND e.created_by_org = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$eventId, $orgId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        echo json_encode(["success" => false, "message" => "Event not found or access denied."]);
        exit();
    }

    // 2. Fetch Event Requirements (Logistics)
    $reqQuery = "SELECT * FROM event_requirements WHERE event_id = ?";
    $reqStmt = $db->prepare($reqQuery);
    $reqStmt->execute([$eventId]);
    $requirements = $reqStmt->fetch(PDO::FETCH_ASSOC);

    // 3. Fetch Approval History
    $appQuery = "SELECT department, status, remarks, updated_at FROM event_approvals WHERE event_id = ?";
    $appStmt = $db->prepare($appQuery);
    $appStmt->execute([$eventId]);
    $approvals = $appStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Registrations
    $regQuery = "SELECT 
                    r.registration_id, 
                    r.student_id, 
                    r.status, 
                    r.registration_date,
                    s.firstname, 
                    s.lastname, 
                    s.student_number, 
                    s.college, 
                    s.year_level, 
                    s.section 
                 FROM event_registrations r
                 JOIN students s ON r.student_id = s.student_id
                 WHERE r.event_id = ?
                 ORDER BY r.registration_date DESC";
                 
    $regStmt = $db->prepare($regQuery);
    $regStmt->execute([$eventId]);
    $registrations = $regStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Calculate Stats
    $totalRegistered = count($registrations);
    $totalAttended = 0;
    foreach($registrations as $r) {
        if($r['status'] === 'Attended') $totalAttended++;
    }

    // Return Data
    echo json_encode([
        "success" => true,
        "event" => $event,
        "requirements" => $requirements,
        "approvals" => $approvals, // Added approvals data
        "registrations" => $registrations,
        "stats" => [
            "registered" => $totalRegistered,
            "attended" => $totalAttended,
            "capacity" => $event['attendees_capacity'],
            "available" => $event['attendees_capacity'] - $totalRegistered
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>