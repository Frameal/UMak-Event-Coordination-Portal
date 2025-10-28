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
    // Get parameters
    if (!isset($_GET['student_id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Student ID is required"]);
        exit();
    }
    
    $student_id = $_GET['student_id'];
    $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? $_GET['month'] : date('n');
    
    // Get calendar events for the specified month (all registered events)
    $query = "SELECT e.event_id, e.event_name, e.event_date, e.start_time, e.end_time,
              e.event_type, er.status as registration_status, v.venue_name, v.location
              FROM event_registrations er
              INNER JOIN events e ON er.event_id = e.event_id
              INNER JOIN venues v ON e.venue_id = v.venue_id
              WHERE er.student_id = ? 
              AND er.status != 'Cancelled'
              AND YEAR(e.event_date) = ?
              AND MONTH(e.event_date) = ?
              ORDER BY e.event_date, e.start_time";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id, $year, $month]);
    $calendar_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "calendar_events" => $calendar_events,
        "month" => (int)$month,
        "year" => (int)$year
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>