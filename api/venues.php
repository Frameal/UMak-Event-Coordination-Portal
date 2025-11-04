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
    $query = isset($_GET['id']) ? 
        "SELECT * FROM venues WHERE venue_id = ?" : 
        "SELECT * FROM venues ORDER BY venue_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute(isset($_GET['id']) ? [$_GET['id']] : []);
    
    if (isset($_GET['id'])) {
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $venue ? json_encode(["success" => true, "data" => $venue]) :
            (http_response_code(404) || json_encode(["success" => false, "message" => "Venue not found"]));
    } else {
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

function handlePost($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return sendResponse(400, false, "Invalid JSON: " . json_last_error_msg());
    }
    
    $required = ['venue_name', 'capacity'];
    $missing = array_filter($required, fn($f) => empty($data[$f]));
    
    if (!empty($missing)) {
        return sendResponse(400, false, "Missing required fields: " . implode(', ', $missing));
    }
    
    if (!is_numeric($data['capacity']) || $data['capacity'] < 1) {
        return sendResponse(400, false, "Capacity must be a positive number");
    }
    
    $stmt = $db->prepare("SELECT venue_id FROM venues WHERE venue_name = ?");
    $stmt->execute([$data['venue_name']]);
    if ($stmt->fetch()) return sendResponse(409, false, "Venue name already exists");
    
    $stmt = $db->prepare("INSERT INTO venues (venue_name, capacity, location, facilities) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$data['venue_name'], $data['capacity'], $data['location'] ?? null, $data['facilities'] ?? null]);
    
    $result ? sendResponse(201, true, "Venue created successfully", ["id" => $db->lastInsertId()]) :
        sendResponse(500, false, "Failed to create venue: " . $stmt->errorInfo()[2]);
}

function handlePut($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['venue_id'])) {
        return sendResponse(400, false, "Venue ID is required");
    }
    
    $allowed = ['venue_name', 'capacity', 'location', 'facilities', 'is_available'];
    $fields = $values = [];
    
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($fields)) return sendResponse(400, false, "No fields to update");
    
    $values[] = $data['venue_id'];
    $stmt = $db->prepare("UPDATE venues SET " . implode(', ', $fields) . " WHERE venue_id = ?");
    
    $stmt->execute($values) ? sendResponse(200, true, "Venue updated successfully") :
        sendResponse(500, false, "Failed to update venue");
}

function handleDelete($db) {
    if (!isset($_GET['id'])) return sendResponse(400, false, "Venue ID is required");
    
    $stmt = $db->prepare("SELECT venue_id FROM venues WHERE venue_id = ?");
    $stmt->execute([$_GET['id']]);
    if (!$stmt->fetch()) return sendResponse(404, false, "Venue not found");
    
    $stmt = $db->prepare("SELECT COUNT(*) as event_count FROM events WHERE venue_id = ?");
    $stmt->execute([$_GET['id']]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['event_count'];
    
    if ($count > 0) {
        return sendResponse(409, false, "Cannot delete venue. It is being used by $count event(s)");
    }
    
    $stmt = $db->prepare("DELETE FROM venues WHERE venue_id = ?");
    $stmt->execute([$_GET['id']]) ? sendResponse(200, true, "Venue deleted successfully") :
        sendResponse(500, false, "Failed to delete venue");
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
