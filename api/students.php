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
        "message" => "Database connection failed. Please check your database configuration."
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
        $query = "SELECT * FROM students WHERE student_id = ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            echo json_encode([
                "success" => true,
                "data" => $student
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Student not found"
            ]);
        }
    } else {
        $query = "SELECT * FROM students ORDER BY lastname, firstname";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($students);
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
    
    $required = ['student_number', 'lastname', 'firstname', 'email', 'gender'];
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
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email format"
        ]);
        return;
    }
    
    if (strlen($data['student_number']) !== 9) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Student number must be exactly 9 characters"
        ]);
        return;
    }
    
    $checkQuery = "SELECT student_id FROM students WHERE student_number = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$data['student_number']]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Student number already exists"
        ]);
        return;
    }
    
    $checkQuery = "SELECT student_id FROM students WHERE email = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$data['email']]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "message" => "Email already exists"
        ]);
        return;
    }
    
    $defaultPassword = password_hash('umak123', PASSWORD_BCRYPT);
    
    $query = "INSERT INTO students 
              (student_number, lastname, firstname, middlename, password, email, 
               gender, year_level, course, contact_number) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($query);
    
    $result = $stmt->execute([
        $data['student_number'],
        $data['lastname'],
        $data['firstname'],
        $data['middlename'] ?? null,
        $defaultPassword,
        $data['email'],
        $data['gender'],
        $data['year_level'] ?? null,
        $data['course'] ?? null,
        $data['contact_number'] ?? null
    ]);
    
    if ($result) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Student created successfully",
            "id" => $db->lastInsertId(),
            "student_number" => $data['student_number']
        ]);
    } else {
        http_response_code(500);
        $error = $stmt->errorInfo();
        echo json_encode([
            "success" => false,
            "message" => "Failed to create student: " . $error[2]
        ]);
    }
}

function handlePut($db) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!isset($data['student_id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Student ID is required"
        ]);
        return;
    }
    
    $fields = [];
    $values = [];
    
    $allowedFields = ['lastname', 'firstname', 'middlename', 'email', 'gender', 
                      'year_level', 'course', 'contact_number'];
    
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
    
    $values[] = $data['student_id'];
    
    $query = "UPDATE students SET " . implode(', ', $fields) . " WHERE student_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($values)) {
        echo json_encode([
            "success" => true,
            "message" => "Student updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to update student"
        ]);
    }
}

function handleDelete($db) {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Student ID is required"
        ]);
        return;
    }
    
    $checkQuery = "SELECT student_id FROM students WHERE student_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$_GET['id']]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Student not found"
        ]);
        return;
    }
    
    $query = "DELETE FROM students WHERE student_id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$_GET['id']])) {
        echo json_encode([
            "success" => true,
            "message" => "Student deleted successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Failed to delete student"
        ]);
    }
}
?>