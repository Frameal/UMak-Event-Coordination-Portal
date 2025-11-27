<?php
// File: api/admin_students.php

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

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit();
}

function sendResponse($code, $success, $message, $data = null) {
    http_response_code($code);
    $response = ["success" => $success, "message" => $message];
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response);
}

// --- HANDLERS ---

function handleGet($db) {
    // 1. AUTOMATICALLY UPDATE INACTIVE STATUS
    // Set is_active = 0 for students who haven't logged in for 1 year
    try {
        $updateSql = "UPDATE students SET is_active = 0 WHERE last_login < DATE_SUB(NOW(), INTERVAL 1 YEAR) AND is_active = 1";
        $db->query($updateSql);
    } catch (Exception $e) {
        // Continue even if update fails, just log silently
    }

    $query = isset($_GET['id']) ? 
        "SELECT * FROM students WHERE student_id = ? LIMIT 1" : 
        "SELECT * FROM students ORDER BY lastname, firstname";
    
    $stmt = $db->prepare($query);
    $stmt->execute(isset($_GET['id']) ? [$_GET['id']] : []);
    
    if (isset($_GET['id'])) {
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($student) unset($student['password']); 
        
        echo $student ? json_encode(["success" => true, "data" => $student]) : 
            (http_response_code(404) || json_encode(["success" => false, "message" => "Student not found"]));
    } else {
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($students as &$s) unset($s['password']);
        echo json_encode($students);
    }
}

function handlePost($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return sendResponse(400, false, "Invalid JSON");
    }
    
    $required = ['student_number', 'lastname', 'firstname', 'email', 'gender'];
    $missing = array_filter($required, fn($f) => empty($data[$f]));
    
    if (!empty($missing)) {
        return sendResponse(400, false, "Missing fields: " . implode(', ', $missing));
    }
    
    foreach (['student_number', 'email'] as $field) {
        $stmt = $db->prepare("SELECT student_id FROM students WHERE $field = ?");
        $stmt->execute([$data[$field]]);
        if ($stmt->fetch()) {
            return sendResponse(409, false, ucfirst(str_replace('_', ' ', $field)) . " already exists");
        }
    }
    
    $rawPassword = !empty($data['password']) ? $data['password'] : 'umak123';
    $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

    // Default is_active to 1 (Active) for new students
    $isActive = isset($data['is_active']) ? $data['is_active'] : 1;

    $query = "INSERT INTO students (student_number, lastname, firstname, middlename, password, email, gender, college, year_level, section, course, contact_number, is_active) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    
    $result = $stmt->execute([
        $data['student_number'], 
        $data['lastname'], 
        $data['firstname'], 
        $data['middlename'] ?? null, 
        $hashedPassword, 
        $data['email'], 
        $data['gender'], 
        $data['college'] ?? null,
        $data['year_level'] ?? null, 
        $data['section'] ?? null,
        $data['course'] ?? null, 
        $data['contact_number'] ?? null,
        $isActive
    ]);
    
    $result ? sendResponse(201, true, "Student created successfully", ["id" => $db->lastInsertId()]) :
        sendResponse(500, false, "Failed to create student.");
}

function handlePut($db) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['student_id'])) {
        return sendResponse(400, false, "Student ID is required");
    }
    
    // Added 'is_active' to allowed fields
    $allowed = ['lastname', 'firstname', 'middlename', 'email', 'gender', 'college', 'year_level', 'section', 'course', 'contact_number', 'is_active', 'password'];
    $fields = $values = [];
    
    if (isset($data['password']) && !empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    } else {
        unset($data['password']);
    }

    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        return sendResponse(400, false, "No fields to update");
    }
    
    $values[] = $data['student_id'];
    $stmt = $db->prepare("UPDATE students SET " . implode(', ', $fields) . " WHERE student_id = ?");
    
    $stmt->execute($values) ? sendResponse(200, true, "Student updated successfully") :
        sendResponse(500, false, "Failed to update student");
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        return sendResponse(400, false, "Student ID is required");
    }
    
    $stmt = $db->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $stmt->execute([$_GET['id']]);
    if (!$stmt->fetch()) return sendResponse(404, false, "Student not found");
    
    $stmt = $db->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->execute([$_GET['id']]) ? sendResponse(200, true, "Student deleted successfully") :
        sendResponse(500, false, "Failed to delete student");
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