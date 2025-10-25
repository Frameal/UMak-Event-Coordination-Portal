<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
file_put_contents('events_debug.log', "Method: " . $method . "\n", FILE_APPEND);

try {
    switch($method) {
        case 'GET':
            $query = "SELECT e.*, v.venue_name,
                     (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id AND er.status = 'Registered') as registered_count
                     FROM events e 
                     LEFT JOIN venues v ON e.venue_id = v.venue_id 
                     ORDER BY e.event_date ASC, e.start_time ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($events);
            break;
            
        case 'POST':
            file_put_contents('events_debug.log', "Entering POST section\n", FILE_APPEND);
            
            $input = file_get_contents("php://input");
            file_put_contents('events_debug.log', "Raw input: " . $input . "\n", FILE_APPEND);
            
            $data = json_decode($input, true);
            file_put_contents('events_debug.log', "Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);
            
            if (!$data) {
                $error = "Invalid JSON: " . json_last_error_msg();
                file_put_contents('events_debug.log', $error . "\n", FILE_APPEND);
                echo json_encode(["message" => $error]);
                exit();
            }
            
            // Validate required fields
            $required = ['event_name', 'event_date', 'start_time', 'end_time', 'venue_id', 'attendees_capacity'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $error = "Missing required field: " . $field;
                    file_put_contents('events_debug.log', $error . "\n", FILE_APPEND);
                    echo json_encode(["message" => $error]);
                    exit();
                }
            }
            
            $query = "INSERT INTO events (event_name, description, event_date, start_time, end_time, venue_id, attendees_capacity, status, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            file_put_contents('events_debug.log', "Query: " . $query . "\n", FILE_APPEND);
            
            $stmt = $db->prepare($query);
            
            $params = [
                $data['event_name'],
                $data['description'] ?? null,
                $data['event_date'],
                $data['start_time'],
                $data['end_time'],
                $data['venue_id'],
                $data['attendees_capacity'],
                $data['status'] ?? 'Published',
                1 // Default created_by admin ID
            ];
            
            file_put_contents('events_debug.log', "Parameters: " . print_r($params, true) . "\n", FILE_APPEND);
            
            if($stmt->execute($params)) {
                $success = "Event created successfully with ID: " . $db->lastInsertId();
                file_put_contents('events_debug.log', $success . "\n", FILE_APPEND);
                echo json_encode(["message" => "Event created successfully", "id" => $db->lastInsertId()]);
            } else {
                $error = $stmt->errorInfo();
                $error_msg = "Execute failed: " . print_r($error, true);
                file_put_contents('events_debug.log', $error_msg . "\n", FILE_APPEND);
                echo json_encode(["message" => "Unable to create event: " . $error[2]]);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $query = "DELETE FROM events WHERE event_id = ?";
                $stmt = $db->prepare($query);
                
                if($stmt->execute([$_GET['id']])) {
                    echo json_encode(["message" => "Event deleted successfully"]);
                } else {
                    echo json_encode(["message" => "Unable to delete event"]);
                }
            }
            break;
            
        default:
            echo json_encode(["message" => "Method not allowed: " . $method]);
            break;
    }
} catch (Exception $e) {
    $error = "Exception: " . $e->getMessage();
    file_put_contents('events_debug.log', $error . "\n", FILE_APPEND);
    echo json_encode(["error" => $e->getMessage()]);
}
?>