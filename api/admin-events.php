<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(http_response_code(200));

require_once __DIR__ . '/database.php';

$db = (new Database())->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

function sendResponse($code, $success, $message, $data = null) {
    http_response_code($code);
    $response = ["success" => $success, "message" => $message];
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response);
}

function handleGet($db) {
    $baseQuery = "SELECT e.*, v.venue_name, v.location" . 
        (isset($_GET['id']) ? ", v.capacity as venue_capacity" : "") . 
        ", (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id AND er.status = 'Registered') as registered_count
         FROM events e LEFT JOIN venues v ON e.venue_id = v.venue_id";
    
    if (isset($_GET['id'])) {
        $stmt = $db->prepare($baseQuery . " WHERE e.event_id = ?");
        $stmt->execute([$_GET['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $event ? json_encode(["success" => true, "data" => $event]) :
            (http_response_code(404) || json_encode(["success" => false, "message" => "Event not found"]));
    } else {
        $stmt = $db->prepare($baseQuery . " ORDER BY e.event_date ASC, e.start_time ASC");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

function handlePost($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return sendResponse(400, false, "Invalid JSON: " . json_last_error_msg());
    }
    
    $required = ['event_name', 'event_date', 'start_time', 'end_time', 'venue_id', 'attendees_capacity'];
    $missing = array_filter($required, fn($f) => empty($data[$f]));
    
    if (!empty($missing)) {
        return sendResponse(400, false, "Missing required fields: " . implode(', ', $missing));
    }
    
    $date = DateTime::createFromFormat('Y-m-d', $data['event_date']);
    if (!$date || $date->format('Y-m-d') !== $data['event_date']) {
        return sendResponse(400, false, "Invalid date format. Use YYYY-MM-DD");
    }
    
    $today = (new DateTime())->setTime(0, 0, 0);
    if ($date < $today) {
        return sendResponse(400, false, "Event date cannot be in the past");
    }
    
    if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['start_time']) ||
        !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $data['end_time'])) {
        return sendResponse(400, false, "Invalid time format. Use HH:MM or HH:MM:SS");
    }
    
    if (strtotime($data['end_time']) <= strtotime($data['start_time'])) {
        return sendResponse(400, false, "End time must be after start time");
    }
    
    if (!is_numeric($data['attendees_capacity']) || $data['attendees_capacity'] < 1) {
        return sendResponse(400, false, "Attendees capacity must be a positive number");
    }
    
    $stmt = $db->prepare("SELECT venue_id, capacity, is_available FROM venues WHERE venue_id = ?");
    $stmt->execute([$data['venue_id']]);
    $venue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venue) return sendResponse(404, false, "Venue not found");
    if (!$venue['is_available']) return sendResponse(400, false, "This venue is not available for booking");
    if ($data['attendees_capacity'] > $venue['capacity']) {
        return sendResponse(400, false, "Event capacity ({$data['attendees_capacity']}) exceeds venue capacity ({$venue['capacity']})");
    }
    
    $stmt = $db->prepare("SELECT event_id, event_name, start_time, end_time FROM events 
        WHERE venue_id = ? AND event_date = ? AND status NOT IN ('Cancelled', 'Completed')
        AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
    $stmt->execute([$data['venue_id'], $data['event_date'], 
        $data['start_time'], $data['start_time'], $data['end_time'], $data['end_time'], 
        $data['start_time'], $data['end_time']]);
    $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($conflict) {
        return sendResponse(409, false, "Venue is already booked for this time slot", [
            "conflict" => ["event_name" => $conflict['event_name'], "time" => $conflict['start_time'] . " - " . $conflict['end_time']]
        ]);
    }
    
    $stmt = $db->prepare("INSERT INTO events (event_name, description, event_date, start_time, end_time, venue_id, attendees_capacity, status, created_by_admin) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $result = $stmt->execute([$data['event_name'], $data['description'] ?? null, $data['event_date'], 
        $data['start_time'], $data['end_time'], $data['venue_id'], $data['attendees_capacity'], 
        $data['status'] ?? 'Published', 1]);
    
    $result ? sendResponse(201, true, "Event created successfully", 
        ["id" => $db->lastInsertId(), "event_name" => $data['event_name']]) :
        sendResponse(500, false, "Failed to create event: " . $stmt->errorInfo()[2]);
}

function handlePut($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['event_id'])) {
        return sendResponse(400, false, "Event ID is required");
    }
    
    $stmt = $db->prepare("SELECT event_id FROM events WHERE event_id = ?");
    $stmt->execute([$data['event_id']]);
    if (!$stmt->fetch()) return sendResponse(404, false, "Event not found");
    
    $allowed = ['event_name', 'description', 'event_date', 'start_time', 'end_time', 'venue_id', 'attendees_capacity', 'status'];
    $fields = $values = [];
    
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($fields)) return sendResponse(400, false, "No fields to update");
    
    $values[] = $data['event_id'];
    $stmt = $db->prepare("UPDATE events SET " . implode(', ', $fields) . " WHERE event_id = ?");
    
    $stmt->execute($values) ? sendResponse(200, true, "Event updated successfully") :
        sendResponse(500, false, "Failed to update event");
}

function handleDelete($db) {
    if (!isset($_GET['id'])) return sendResponse(400, false, "Event ID is required");
    
    $stmt = $db->prepare("SELECT event_id, event_name FROM events WHERE event_id = ?");
    $stmt->execute([$_GET['id']]);
    if (!$stmt->fetch()) return sendResponse(404, false, "Event not found");
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?");
    $stmt->execute([$_GET['id']]);
    $regCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $db->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->execute([$_GET['id']]) ? 
        sendResponse(200, true, "Event deleted successfully" . ($regCount > 0 ? " (including $regCount registration(s))" : "")) :
        sendResponse(500, false, "Failed to delete event");
}

try {
    $handlers = ['GET' => 'handleGet', 'POST' => 'handlePost', 'PUT' => 'handlePut', 'DELETE' => 'handleDelete'];
    isset($handlers[$_SERVER['REQUEST_METHOD']]) ? 
        $handlers[$_SERVER['REQUEST_METHOD']]($db) : 
        sendResponse(405, false, "Method not allowed");
} catch (Exception $e) {
    sendResponse(500, false, "Server error: " . $e->getMessage());
}
?>
