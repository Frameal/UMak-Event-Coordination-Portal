<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            handleGet($db);
            break;
            
        case 'POST':
            handlePost($db);
            break;
            
        case 'PUT':
            handlePut($db);
            break;
            
        case 'DELETE':
            handleDelete($db);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Method not allowed"
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}

function handleGet($db) {
    if (isset($_GET['id'])) {
        $query = "SELECT e.*, v.venue_name, v.location, v.capacity as venue_capacity,
                 (SELECT COUNT(*) FROM event_registrations er 
                  WHERE er.event_id = e.event_id AND er.status = 'Registered') as registered_count
                 FROM events e 
                 LEFT JOIN venues v ON e.venue_id = v.venue_id 
                 WHERE e.event_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event) {
            echo json_encode([
                "success" => true,
                "data" => $event
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Event not found"
            ]);
        }
    } else {
        $query = "SELECT e.*, v.venue_name, v.location,
                 (SELECT COUNT(*) FROM event_registrations er 
                  WHERE er.event_id = e.event_id AND er.status = 'Registered') as registered_count
                 FROM events e 
                 LEFT JOIN venues v ON e.venue_id = v.venue_id 
                 ORDER BY e.event_date ASC, e.start_time ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($events);
    }
}

function handlePost($db) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid JSON: " . json_last_error_msg()
        ]);
        return;
    }
    
    $required = ['event_name', 'event_date', 'start_time', 'end_time', 'venue_id', 'attendees_capacity'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing required fields: " . implode(', ', $missing)
        ]);
        return;
    }
    
    $date = DateTime::createFromFormat('Y-m-d', $data['event_date']);
    if (!$date || $date->format('Y-m-d') !== $data['event_date']) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid date format. Use YYYY-MM-DD"
        ]);
        return;
    }
    
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if ($date < $today) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Event date cannot be in the past"
        ]);
        return;
    }
    
    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['start_time']) ||
        !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['end_time'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid time format. Use HH:MM or HH:MM:SS"
        ]);
        return;
    }
    
    $startTime = strtotime($data['start_time']);
    $endTime = strtotime($data['end_time']);
    if ($endTime <= $startTime) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "End time must be after start time"
        ]);
        return;
    }
    
    if (!is_numeric($data['attendees_capacity']) || $data['attendees_capacity'] < 1) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Attendees capacity must be a positive number"
        ]);
        return;
    }
    
    $venueCheck = "SELECT venue_id, capacity, is_available FROM venues WHERE venue_id = ?";
    $venueStmt = $db->prepare($venueCheck);
    $venueStmt->execute([$data['venue_id']]);
    $venue = $venueStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venue) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Venue not found"
        ]);
        return;
    }
    
    if (!$venue['is_available']) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "This venue is not available for booking"
        ]);
        return;
    }
    
    if ($data['attendees_capacity'] > $venue['capacity']) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Event capacity ({$data['attendees_capacity']}) exceeds venue capacity ({$venue['capacity']})"
        ]);
        return;
    }
    
    $conflictQuery = "SELECT event_id, event_name, start_time, end_time FROM events 
                     WHERE venue_id = ? 
                     AND event_date = ? 
                     AND status NOT IN ('Cancelled', 'Completed')
                     AND (
                         (start_time <= ? AND end_time > ?) OR
                         (start_time < ? AND end_time >= ?) OR
                         (start_time >= ? AND end_time <= ?)
                     )";
    $conflictStmt = $db->prepare($conflictQuery);
    $conflictStmt->execute([
        $data['venue_id'],
        $data['event_date'],
        $data['start_time'], $data['start_time'],
        $data['end_time'], $data['end_time'],
        $data['start_time'], $data['end_time']
    ]);
    $conflict = $conflictStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conflict) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Venue is already booked for this time slot",
            "conflict" => [
                "event_name" => $conflict['event_name'],
                "time" => $conflict['start_time'] . " - " . $conflict['end_time']
            ]
        ]);
        return;
    }
    
    $query = "INSERT INTO events 
              (event_name, description, event_date, start_time, end_time, 
               venue_id, attendees_capacity, status, created_by_admin) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    
    $result = $stmt->execute([
        $data['event_name'],
        $data['description'] ?? null,
        $data['event_date'],
        $data['start_time'],
        $data['end_time'],
        $data['venue_id'],
        $data['attendees_capacity'],
        $data['status'] ?? 'Published',
        1 
    ]);
    
    if ($result) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Event created successfully",
            "id" => $db->lastInsertId(),
            "event_name" => $data['event_name']
        ]);
    } else {
        http_response_code(500);
        $error = $stmt->errorInfo();
        echo json_encode([
            "success" => false,
            "message" => "Failed to create event: " . $error[2]
        ]);
    }
}

function handlePut($db) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!isset($data['event_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Event ID is required"
        ]);
        return;
    }
    
    $checkQuery = "SELECT event_id FROM events WHERE event_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$data['event_id']]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Event not found"
        ]);
        return;
    }
    
    $fields = [];
    $values = [];
    
    $allowedFields = ['event_name', 'description', 'event_date', 'start_time', 'end_time', 
                      'venue_id', 'attendees_capacity', 'status'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "No fields to update"
        ]);
        return;
    }
    
    $values[] = $data['event_id'];
    
    $query = "UPDATE events SET " . implode(', ', $fields) . " WHERE event_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($values)) {
        echo json_encode([
            "success" => true,
            "message" => "Event updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update event"
        ]);
    }
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Event ID is required"
        ]);
        return;
    }
    
    $checkQuery = "SELECT event_id, event_name FROM events WHERE event_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$_GET['id']]);
    $event = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Event not found"
        ]);
        return;
    }
    
    $registrationsQuery = "SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?";
    $registrationsStmt = $db->prepare($registrationsQuery);
    $registrationsStmt->execute([$_GET['id']]);
    $registrations = $registrationsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($registrations['count'] > 0) {
        $deleteQuery = "DELETE FROM events WHERE event_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        
        if ($deleteStmt->execute([$_GET['id']])) {
            echo json_encode([
                "success" => true,
                "message" => "Event deleted successfully (including {$registrations['count']} registration(s))"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete event"
            ]);
        }
    } else {
        $deleteQuery = "DELETE FROM events WHERE event_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        
        if ($deleteStmt->execute([$_GET['id']])) {
            echo json_encode([
                "success" => true,
                "message" => "Event deleted successfully"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete event"
            ]);
        }
    }
}
?>