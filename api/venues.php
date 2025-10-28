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
        $query = "SELECT * FROM venues WHERE venue_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['id']]);
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($venue) {
            echo json_encode([
                "success" => true,
                "data" => $venue
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Venue not found"
            ]);
        }
    } else {
        $query = "SELECT * FROM venues ORDER BY venue_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($venues);
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
    
    $required = ['venue_name', 'capacity'];
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
    
    if (!is_numeric($data['capacity']) || $data['capacity'] < 1) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Capacity must be a positive number"
        ]);
        return;
    }
    
    $checkQuery = "SELECT venue_id FROM venues WHERE venue_name = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$data['venue_name']]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Venue name already exists"
        ]);
        return;
    }
    
    $query = "INSERT INTO venues (venue_name, capacity, location, facilities) 
              VALUES (?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    
    $result = $stmt->execute([
        $data['venue_name'],
        $data['capacity'],
        $data['location'] ?? null,
        $data['facilities'] ?? null
    ]);
    
    if ($result) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Venue created successfully",
            "id" => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        $error = $stmt->errorInfo();
        echo json_encode([
            "success" => false,
            "message" => "Failed to create venue: " . $error[2]
        ]);
    }
}

function handlePut($db) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!isset($data['venue_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Venue ID is required"
        ]);
        return;
    }
    
    $fields = [];
    $values = [];
    
    $allowedFields = ['venue_name', 'capacity', 'location', 'facilities', 'is_available'];
    
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
    
    $values[] = $data['venue_id'];
    
    $query = "UPDATE venues SET " . implode(', ', $fields) . " WHERE venue_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($values)) {
        echo json_encode([
            "success" => true,
            "message" => "Venue updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update venue"
        ]);
    }
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Venue ID is required"
        ]);
        return;
    }
    
    $checkQuery = "SELECT venue_id FROM venues WHERE venue_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$_GET['id']]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Venue not found"
        ]);
        return;
    }
    
    $usageQuery = "SELECT COUNT(*) as event_count FROM events WHERE venue_id = ?";
    $usageStmt = $db->prepare($usageQuery);
    $usageStmt->execute([$_GET['id']]);
    $usage = $usageStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usage['event_count'] > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Cannot delete venue. It is being used by {$usage['event_count']} event(s)"
        ]);
        return;
    }
    
    $query = "DELETE FROM venues WHERE venue_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$_GET['id']])) {
        echo json_encode([
            "success" => true,
            "message" => "Venue deleted successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to delete venue"
        ]);
    }
}
?>
        