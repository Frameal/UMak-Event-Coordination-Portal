<?php
// File: api/admin_venues.php

error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

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

// --- HELPER: Handle Image Upload ---
function handleImageUpload() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Target directory relative to this API file
    // Going back one level (..) then into images/Venues/
    $targetDir = "../images/Venues/";
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileExtension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($fileExtension, $allowedTypes)) {
        return null; 
    }

    // Generate unique filename to prevent overwrites
    $newFileName = uniqid('VENUE_') . '.' . $fileExtension;
    $targetFile = $targetDir . $newFileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        // Return the path relative to the PAGE (not the API)
        // Pages are in pages/admin/, images are in ../../images/Venues/
        return "../../images/Venues/" . $newFileName; 
    }

    return null;
}

// --- HANDLERS ---

function handleGet($db) {
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM venues WHERE venue_id = ? LIMIT 1");
        $stmt->execute([$_GET['id']]);
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo $venue ? json_encode(["success" => true, "data" => $venue]) : 
            (http_response_code(404) || json_encode(["success" => false, "message" => "Venue not found"]));
    } else {
        $stmt = $db->prepare("SELECT * FROM venues ORDER BY venue_name ASC");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

function handlePost($db) {
    // Use $_POST directly for FormData
    $venue_name = $_POST['venue_name'] ?? '';
    $capacity = $_POST['capacity'] ?? '';
    $location = $_POST['location'] ?? '';
    $amenities = $_POST['amenities'] ?? '';
    $description = $_POST['description'] ?? '';
    $is_available = $_POST['is_available'] ?? 1;

    if (empty($venue_name) || empty($capacity) || empty($location)) {
        return sendResponse(400, false, "Venue Name, Capacity, and Location are required.");
    }

    $check = $db->prepare("SELECT venue_id FROM venues WHERE venue_name = ?");
    $check->execute([$venue_name]);
    if ($check->fetch()) {
        return sendResponse(409, false, "Venue name already exists.");
    }

    // Handle Image
    $imageUrl = handleImageUpload();

    $query = "INSERT INTO venues (venue_name, capacity, location, amenities, description, is_available, image_url) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    $result = $stmt->execute([
        $venue_name, $capacity, $location, $amenities, $description, $is_available, $imageUrl
    ]);

    $result ? sendResponse(201, true, "Venue created successfully") : sendResponse(500, false, "Failed to create venue");
}

function handlePut($db) {
    // Since PHP doesn't parse multipart/form-data on PUT, we check if it came as POST with an ID
    // If we are using the frontend correctly, we will be sending a POST request even for edits if files are involved.
    // Or we extract from raw input if it's JSON.
    
    $inputData = $_POST; // Default to POST for file handling
    if (empty($inputData)) {
        // Fallback for JSON (status toggle)
        $inputData = json_decode(file_get_contents("php://input"), true);
    }

    if (empty($inputData['venue_id'])) {
        return sendResponse(400, false, "Venue ID is required for updates.");
    }

    $imageUrl = handleImageUpload();
    
    $allowed = ['venue_name', 'capacity', 'location', 'amenities', 'description', 'is_available'];
    $fields = [];
    $values = [];

    foreach ($allowed as $field) {
        if (isset($inputData[$field])) {
            $fields[] = "$field = ?";
            $values[] = $inputData[$field];
        }
    }

    if ($imageUrl) {
        $fields[] = "image_url = ?";
        $values[] = $imageUrl;
    }

    if (empty($fields)) {
        return sendResponse(400, false, "No fields to update");
    }

    $values[] = $inputData['venue_id'];
    $stmt = $db->prepare("UPDATE venues SET " . implode(', ', $fields) . " WHERE venue_id = ?");
    
    $stmt->execute($values) ? sendResponse(200, true, "Venue updated successfully") : sendResponse(500, false, "Failed to update venue");
}

function handleDelete($db) {
    if (!isset($_GET['id'])) return sendResponse(400, false, "ID required");
    
    $check = $db->prepare("SELECT COUNT(*) as count FROM events WHERE venue_id = ?");
    $check->execute([$_GET['id']]);
    $usage = $check->fetch(PDO::FETCH_ASSOC);

    if ($usage['count'] > 0) {
        return sendResponse(409, false, "Cannot delete venue: It is associated with existing events.");
    }

    $stmt = $db->prepare("DELETE FROM venues WHERE venue_id = ?");
    $stmt->execute([$_GET['id']]) ? sendResponse(200, true, "Venue deleted successfully") : sendResponse(500, false, "Failed to delete venue");
}

// --- ROUTER ---
$method = $_SERVER['REQUEST_METHOD'];

// If POST has venue_id, treat as PUT (Update) to support file uploads in HTML forms
if ($method === 'POST' && isset($_POST['venue_id']) && !empty($_POST['venue_id'])) {
    handlePut($db);
} elseif ($method === 'GET') {
    handleGet($db);
} elseif ($method === 'POST') {
    handlePost($db);
} elseif ($method === 'PUT') {
    handlePut($db);
} elseif ($method === 'DELETE') {
    handleDelete($db);
} else {
    sendResponse(405, false, "Method not allowed");
}
?>