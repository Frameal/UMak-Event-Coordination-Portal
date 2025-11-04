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
    $baseQuery = "SELECT er.*, s.student_number, s.firstname, s.lastname, s.email, 
        e.event_name, e.event_date, e.start_time, e.end_time
        FROM event_registrations er
        JOIN students s ON er.student_id = s.student_id
        JOIN events e ON er.event_id = e.event_id";
    
    if (isset($_GET['id'])) {
        $stmt = $db->prepare($baseQuery . " WHERE er.registration_id = ?");
        $stmt->execute([$_GET['id']]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $registration ? json_encode(["success" => true, "data" => $registration]) :
            (http_response_code(404) || json_encode(["success" => false, "message" => "Registration not found"]));
    } else {
        $stmt = $db->prepare($baseQuery . " ORDER BY er.registration_date DESC");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

function handlePost($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return sendResponse(400, false, "Invalid JSON: " . json_last_error_msg());
    }
    
    if (empty($data['student_id']) || empty($data['event_id'])) {
        return sendResponse(400, false, "Student ID and Event ID are required");
    }
    
    $stmt = $db->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $stmt->execute([$data['student_id']]);
    if (!$stmt->fetch()) return sendResponse(404, false, "Student not found");
    
    $stmt = $db->prepare("SELECT event_id, attendees_capacity, status FROM events WHERE event_id = ?");
    $stmt->execute([$data['event_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) return sendResponse(404, false, "Event not found");
    if (in_array($event['status'], ['Cancelled', 'Completed'])) {
        return sendResponse(400, false, "This event is not accepting registrations");
    }
    
    $stmt = $db->prepare("SELECT registration_id FROM event_registrations WHERE student_id = ? AND event_id = ?");
    $stmt->execute([$data['student_id'], $data['event_id']]);
    if ($stmt->fetch()) return sendResponse(409, false, "Student is already registered for this event");
    
    $stmt = $db->prepare("SELECT COUNT(*) as current_count FROM event_registrations WHERE event_id = ? AND status = 'Registered'");
    $stmt->execute([$data['event_id']]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['current_count'];
    
    if ($count >= $event['attendees_capacity']) {
        return sendResponse(409, false, "Event is full. No more slots available");
    }
    
    $qr_code = 'QR-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
    $stmt = $db->prepare("INSERT INTO event_registrations (event_id, student_id, qr_code, status) VALUES (?, ?, ?, 'Registered')");
    $result = $stmt->execute([$data['event_id'], $data['student_id'], $qr_code]);
    
    $result ? sendResponse(201, true, "Registration successful", 
        ["qr_code" => $qr_code, "registration_id" => $db->lastInsertId()]) :
        sendResponse(500, false, "Failed to create registration: " . $stmt->errorInfo()[2]);
}

function handlePut($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['registration_id'])) {
        return sendResponse(400, false, "Registration ID is required");
    }
    
    $allowed = ['status', 'attended_at', 'notes'];
    $fields = $values = [];
    
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($fields)) return sendResponse(400, false, "No fields to update");
    
    $values[] = $data['registration_id'];
    $stmt = $db->prepare("UPDATE event_registrations SET " . implode(', ', $fields) . " WHERE registration_id = ?");
    
    $stmt->execute($values) ? sendResponse(200, true, "Registration updated successfully") :
        sendResponse(500, false, "Failed to update registration");
}

function handleDelete($db) {
    if (!isset($_GET['id'])) return sendResponse(400, false, "Registration ID is required");
    
    $stmt = $db->prepare("SELECT registration_id, status FROM event_registrations WHERE registration_id = ?");
    $stmt->execute([$_GET['id']]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) return sendResponse(404, false, "Registration not found");
    if ($registration['status'] === 'Attended') {
        return sendResponse(400, false, "Cannot cancel registration. Student has already attended the event");
    }
    
    $stmt = $db->prepare("DELETE FROM event_registrations WHERE registration_id = ?");
    $stmt->execute([$_GET['id']]) ? sendResponse(200, true, "Registration cancelled successfully") :
        sendResponse(500, false, "Failed to cancel registration");
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
