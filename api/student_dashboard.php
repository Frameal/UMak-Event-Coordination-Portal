<?php
// File: umak_ecp/api/student_dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../config/database.php';
include_once 'update_status_helper.php';

$database = new Database();
$db = $database->getConnection();

// Run status updates
updateEventStatuses($db);

if (!isset($_GET['student_id'])) {
    echo json_encode(["success" => false, "message" => "Student ID is required"]);
    exit();
}

$student_id = $_GET['student_id'];

try {
    // 0. GET STUDENT DETAILS FOR FILTERING
    $stuSql = "SELECT college, year_level FROM students WHERE student_id = ?";
    $stuStmt = $db->prepare($stuSql);
    $stuStmt->execute([$student_id]);
    $studentInfo = $stuStmt->fetch(PDO::FETCH_ASSOC);
    
    $stuCollege = $studentInfo['college'];
    $stuYear = $studentInfo['year_level'];

    // 1. Stats Queries
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE student_id = ? AND status != 'Cancelled'");
    $stmt->execute([$student_id]);
    $registered_count = $stmt->fetch()['count'];

    $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE student_id = ? AND status = 'Attended'");
    $stmt->execute([$student_id]);
    $attended_count = $stmt->fetch()['count'];

    // Available Events (Filtered by College/Year)
    $availSql = "SELECT COUNT(*) as count FROM events e 
                 WHERE e.status IN ('Published', 'Registration Open') 
                 AND e.event_date >= CURDATE()
                 AND (e.target_college = 'All' OR e.target_college = ?)
                 AND (e.target_year_level = 'All' OR e.target_year_level = ? OR e.target_year_level LIKE CONCAT('%', ?, '%'))
                 AND e.event_id NOT IN (SELECT event_id FROM event_registrations WHERE student_id = ? AND status != 'Cancelled')";
    $stmtAvail = $db->prepare($availSql);
    $stmtAvail->execute([$stuCollege, $stuYear, $stuYear, $student_id]);
    $available_count = $stmtAvail->fetch()['count'];
    
    // Pending Evals
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations er JOIN events e ON er.event_id=e.event_id WHERE er.student_id = ? AND er.status='Attended' AND er.has_evaluated=0 AND e.requires_evaluation=1");
    $stmt->execute([$student_id]);
    $pending_eval_count = $stmt->fetch()['count'];

    // 2. Upcoming Events List
    $upcomingQ = "SELECT e.*, v.venue_name, 
                  CASE WHEN er.registration_id IS NOT NULL THEN 'Registered' ELSE 'Not Registered' END as registration_status
                  FROM events e
                  LEFT JOIN venues v ON e.venue_id = v.venue_id
                  LEFT JOIN event_registrations er ON e.event_id = er.event_id AND er.student_id = ?
                  WHERE e.status IN ('Published', 'Registration Open') AND e.event_date >= CURDATE()
                  AND (e.target_college = 'All' OR e.target_college = ?)
                  ORDER BY e.event_date ASC LIMIT 5";
    $stmtUp = $db->prepare($upcomingQ);
    $stmtUp->execute([$student_id, $stuCollege]);
    $upcoming = $stmtUp->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. CALENDAR EVENTS (Filtered by College/Year + ALL Published)
    $calQ = "SELECT 
                e.event_id, 
                e.event_name as title, 
                e.event_date as start, 
                CONCAT(e.event_date, 'T', e.start_time) as start_time,
                CONCAT(e.event_date, 'T', e.end_time) as end_time,
                e.status,
                v.venue_name,
                e.target_college,
                e.attendees_capacity,
                e.event_type,
                CASE WHEN er.registration_id IS NOT NULL THEN 1 ELSE 0 END as is_registered
              FROM events e
              LEFT JOIN venues v ON e.venue_id = v.venue_id
              LEFT JOIN event_registrations er ON e.event_id = er.event_id AND er.student_id = ? 
              WHERE e.status IN ('Published', 'Registration Open', 'Ongoing', 'Completed', 'Registration Closed')
              AND (e.target_college = 'All' OR e.target_college = ?)
              ORDER BY e.event_date";
              
    $stmtCal = $db->prepare($calQ);
    $stmtCal->execute([$student_id, $stuCollege]);
    $calendar_events = $stmtCal->fetchAll(PDO::FETCH_ASSOC);

    foreach($calendar_events as &$ev) {
        if ($ev['is_registered'] == 1) {
            $ev['color'] = '#28a745'; 
            $ev['title'] = '✓ ' . $ev['title'];
        } else {
            $ev['color'] = '#022d6d'; 
        }
        // Implicit pass of extra details
    }
    
    echo json_encode([
        "success" => true,
        "stats" => ["registered" => $regCount, "attended" => $attCount, "pending_eval" => $evalCount, "available_events" => $available_count],
        "upcoming_events" => $upcoming,
        "calendar_events" => $calendar_events
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>