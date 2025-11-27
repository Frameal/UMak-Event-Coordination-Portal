<?php
// File: umak_ecp/api/org_dashboard.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../config/database.php';
include_once 'update_status_helper.php';

$database = new Database();
$db = $database->getConnection();

// Run status updates
updateEventStatuses($db);

if (!isset($_GET['org_id'])) {
    echo json_encode(["success" => false, "message" => "Organization ID is required"]);
    exit();
}

$orgId = $_GET['org_id'];

try {
    // 1. FETCH STATISTICS
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM events WHERE created_by_org = ? AND status IN ('Published', 'Registration Open', 'Ongoing')");
    $stmt->execute([$orgId]);
    $activeEvents = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM events WHERE created_by_org = ? AND (status = 'Draft' OR status = 'Pending Approval')");
    $stmt->execute([$orgId]);
    $pendingApprovals = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations er JOIN events e ON er.event_id = e.event_id WHERE e.created_by_org = ? AND er.has_evaluated = 1");
    $stmt->execute([$orgId]);
    $evaluations = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations er JOIN events e ON er.event_id = e.event_id WHERE e.created_by_org = ? AND er.status = 'Attended'");
    $stmt->execute([$orgId]);
    $totalAttendees = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 2. FETCH CALENDAR EVENTS
    $query = "SELECT 
                e.event_id, 
                e.event_name as title, 
                e.event_date as start,
                CONCAT(e.event_date, 'T', e.start_time) as start_time, 
                CONCAT(e.event_date, 'T', e.end_time) as end_time,
                e.status,
                v.venue_name,
                e.target_college,
                e.attendees_capacity,
                e.event_type
              FROM events e
              LEFT JOIN venues v ON e.venue_id = v.venue_id
              WHERE e.created_by_org = ? AND e.status != 'Cancelled'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$orgId]);
    $calendarEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($calendarEvents as &$event) {
        switch($event['status']) {
            case 'Published': 
            case 'Registration Open': $event['color'] = '#28a745'; break;
            case 'Ongoing': $event['color'] = '#ffc107'; break;
            case 'Draft': 
            case 'Pending Approval': $event['color'] = '#6c757d'; break;
            case 'Completed': $event['color'] = '#022d6d'; break;
            default: $event['color'] = '#17a2b8';
        }
    }

    echo json_encode([
        "success" => true,
        "stats" => [
            "active_events" => $activeEvents,
            "pending_approvals" => $pendingApprovals,
            "evaluations" => $evaluations,
            "total_attendees" => $totalAttendees
        ],
        "calendar" => $calendarEvents
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>