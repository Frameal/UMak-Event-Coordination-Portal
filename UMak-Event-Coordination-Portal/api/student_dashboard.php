<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

try {
    // Get student_id from query parameter
    if (!isset($_GET['student_id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Student ID is required"]);
        exit();
    }
    
    $student_id = $_GET['student_id'];
    
    // Get registered events count
    $query = "SELECT COUNT(*) as count FROM event_registrations WHERE student_id = ? AND status != 'Cancelled'";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $registered_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get available events count (published and registration open, not registered by student)
    $query = "SELECT COUNT(*) as count FROM events e 
              WHERE e.status IN ('Published', 'Registration Open') 
              AND e.event_date >= CURDATE()
              AND e.event_id NOT IN (
                  SELECT event_id FROM event_registrations 
                  WHERE student_id = ? AND status != 'Cancelled'
              )";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $available_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get attended events count
    $query = "SELECT COUNT(*) as count FROM event_registrations WHERE student_id = ? AND status = 'Attended'";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $attended_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get pending post-evaluation count (attended but not evaluated)
    $query = "SELECT COUNT(*) as count FROM event_registrations er
              INNER JOIN events e ON er.event_id = e.event_id
              WHERE er.student_id = ? 
              AND er.status = 'Attended' 
              AND er.has_evaluated = 0
              AND e.requires_evaluation = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $pending_eval_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get upcoming events (registered events that haven't happened yet)
    $query = "SELECT e.*, v.venue_name, v.location,
              er.registration_id, er.qr_code, er.status as registration_status
              FROM event_registrations er
              INNER JOIN events e ON er.event_id = e.event_id
              INNER JOIN venues v ON e.venue_id = v.venue_id
              WHERE er.student_id = ? 
              AND er.status IN ('Registered')
              AND e.event_date >= CURDATE()
              ORDER BY e.event_date ASC, e.start_time ASC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get calendar events (all registered events for the current month)
    $query = "SELECT e.event_id, e.event_name, e.event_date, e.start_time, e.end_time
              FROM event_registrations er
              INNER JOIN events e ON er.event_id = e.event_id
              WHERE er.student_id = ? 
              AND er.status != 'Cancelled'
              AND MONTH(e.event_date) = MONTH(CURDATE())
              AND YEAR(e.event_date) = YEAR(CURDATE())
              ORDER BY e.event_date";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $calendar_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "stats" => [
            "registered_events" => (int)$registered_count,
            "available_events" => (int)$available_count,
            "attended_events" => (int)$attended_count,
            "pending_evaluation" => (int)$pending_eval_count
        ],
        "upcoming_events" => $upcoming_events,
        "calendar_events" => $calendar_events
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>