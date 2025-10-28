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
        $query = "SELECT er.*, 
                  s.student_number, s.firstname, s.lastname, s.email,
                  e.event_name, e.event_date, e.start_time, e.end_time
                  FROM event_registrations er
                  JOIN students s ON er.student_id = s.student_id
                  JOIN events e ON er.event_id = e.event_id
                  WHERE er.registration_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['id']]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($registration) {
            echo json_encode([
                "success" => true,
                "data" => $registration
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Registration not found"
            ]);
        }
    } else {
        $query = "SELECT er.*, 
                  s.student_number, s.firstname, s.lastname, s.email,
                  e.event_name, e.event_date, e.start_time, e.end_time
                  FROM event_registrations er
                  JOIN students s ON er.student_id = s.student_id
                  JOIN events e ON er.event_id = e.event_id
                  ORDER BY er.registration_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($registrations);
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
    
    if (empty($data['student_id']) || empty($data['event_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Student ID and Event ID are required"
        ]);
        return;
    }
    
    $studentCheck = "SELECT student_id FROM students WHERE student_id = ?";
    $studentStmt = $db->prepare($studentCheck);
    $studentStmt->execute([$data['student_id']]);
    
    if (!$studentStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Student not found"
        ]);
        return;
    }
    
    $eventCheck = "SELECT event_id, attendees_capacity, status FROM events WHERE event_id = ?";
    $eventStmt = $db->prepare($eventCheck);
    $eventStmt->execute([$data['event_id']]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Event not found"
        ]);
        return;
    }
    
    if ($event['status'] === 'Cancelled' || $event['status'] === 'Completed') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "This event is not accepting registrations"
        ]);
        return;
    }
    
    $duplicateCheck = "SELECT registration_id FROM event_registrations 
                      WHERE student_id = ? AND event_id = ?";
    $duplicateStmt = $db->prepare($duplicateCheck);
    $duplicateStmt->execute([$data['student_id'], $data['event_id']]);
    
    if ($duplicateStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Student is already registered for this event"
        ]);
        return;
    }
    
    $capacityCheck = "SELECT COUNT(*) as current_count FROM event_registrations 
                     WHERE event_id = ? AND status = 'Registered'";
    $capacityStmt = $db->prepare($capacityCheck);
    $capacityStmt->execute([$data['event_id']]);
    $capacity = $capacityStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($capacity['current_count'] >= $event['attendees_capacity']) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Event is full. No more slots available"
        ]);
        return;
    }
    
    $qr_code = 'QR-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
    
    $query = "INSERT INTO event_registrations (event_id, student_id, qr_code, status) 
              VALUES (?, ?, ?, 'Registered')";
    
    $stmt = $db->prepare($query);
    
    $result = $stmt->execute([
        $data['event_id'],
        $data['student_id'],
        $qr_code
    ]);
    
    if ($result) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Registration successful",
            "qr_code" => $qr_code,
            "registration_id" => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        $error = $stmt->errorInfo();
        echo json_encode([
            "success" => false,
            "message" => "Failed to create registration: " . $error[2]
        ]);
    }
}

function handlePut($db) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!isset($data['registration_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Registration ID is required"
        ]);
        return;
    }
    
    $fields = [];
    $values = [];
    
    $allowedFields = ['status', 'attended_at', 'notes'];
    
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
    
    $values[] = $data['registration_id'];
    
    $query = "UPDATE event_registrations SET " . implode(', ', $fields) . " WHERE registration_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($values)) {
        echo json_encode([
            "success" => true,
            "message" => "Registration updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update registration"
        ]);
    }
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Registration ID is required"
        ]);
        return;
    }
    
    $checkQuery = "SELECT registration_id, status FROM event_registrations WHERE registration_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$_GET['id']]);
    $registration = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Registration not found"
        ]);
        return;
    }
    
    if ($registration['status'] === 'Attended') {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Cannot cancel registration. Student has already attended the event"
        ]);
        return;
    }
    
    $query = "DELETE FROM event_registrations WHERE registration_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$_GET['id']])) {
        echo json_encode([
            "success" => true,
            "message" => "Registration cancelled successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to cancel registration"
        ]);
    }
}
?>