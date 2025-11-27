<?php
// File: api/students.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Helper: Strong Password Check
function isStrongPassword($password) {
    // Min 8 chars, 1 upper, 1 number, 1 special char
    return preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

// Helper function to send JSON response
function sendResponse($code, $success, $message, $data = null) {
    http_response_code($code);
    $response = ["success" => $success, "message" => $message];
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response);
}

try {
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single student details
                $query = "SELECT * FROM students WHERE student_id = ? LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $_GET['id']);
                $stmt->execute();
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($student) {
                    unset($student['password']); 
                    echo json_encode(["success" => true, "data" => $student]);
                } else {
                    sendResponse(404, false, "Student not found");
                }
            } else {
                // Get list of all students
                $query = "SELECT * FROM students ORDER BY lastname, firstname";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Remove passwords
                foreach($students as &$s) {
                    unset($s['password']);
                }
                echo json_encode($students);
            }
            break;
            
        case 'POST':
            $input = file_get_contents("php://input");
            $data = json_decode($input, true);
            
            if (!$data) {
                sendResponse(400, false, "No data received or invalid JSON");
                exit();
            }

            // --- OTP VERIFICATION ---
            if (isset($data['otp'])) {
                $userOtp = $data['otp'];
                $sessionOtp = $_SESSION['signup_otp'] ?? '';
                $sessionEmail = $_SESSION['signup_email'] ?? '';

                if (empty($userOtp) || $userOtp != $sessionOtp) {
                    sendResponse(400, false, "Invalid or expired OTP.");
                    exit();
                }

                if ($data['email'] !== $sessionEmail) {
                    sendResponse(400, false, "Email does not match the verified email.");
                    exit();
                }
            }
            
            // Validate Password Strength
            if (!isStrongPassword($data['password'])) {
                sendResponse(400, false, "Password is too weak. Must be 8+ characters, include uppercase, number, and special char.");
                exit();
            }

            $required_fields = ['studentnumber', 'lastname', 'firstname', 'email', 'gender', 'password'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    sendResponse(400, false, "Missing required fields"); exit();
                }
            }
            
            $checkQuery = "SELECT student_id FROM students WHERE student_number = ? OR email = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$data['studentnumber'], $data['email']]);
            
            if ($checkStmt->rowCount() > 0) {
                sendResponse(409, false, "Student Number or Email already registered.");
                exit();
            }
            
            $query = "INSERT INTO students (student_number, lastname, firstname, middlename, email, gender, year_level, section, college, course, contact_number, password, is_active, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            
            $stmt = $db->prepare($query);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $params = [
                $data['studentnumber'], $data['lastname'], $data['firstname'], $data['middleinitial'] ?? null, 
                $data['email'], $data['gender'], $data['yearlevel'] ?? null,
                $data['section'] ?? null, $data['college'] ?? null,
                $data['course'] ?? null, $data['contactnumber'] ?? null,
                $hashedPassword
            ];
            
            if($stmt->execute($params)) {
                unset($_SESSION['signup_otp']);
                unset($_SESSION['signup_email']);
                sendResponse(201, true, "Student registered successfully", ["id" => $db->lastInsertId()]);
            } else {
                $error = $stmt->errorInfo();
                sendResponse(500, false, "Database error: " . $error[2]);
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['student_id'])) {
                sendResponse(400, false, "Student ID is required for update");
                exit();
            }
            
            $allowed = ['lastname', 'firstname', 'middlename', 'email', 'gender', 'college', 'year_level', 'section', 'course', 'contact_number', 'is_active'];
            $fields = [];
            $values = [];
            
            // Password update check
            if (isset($data['password']) && !empty($data['password'])) {
                 if (!isStrongPassword($data['password'])) {
                    sendResponse(400, false, "New password is too weak.");
                    exit();
                }
                $fields[] = "password = ?";
                $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                sendResponse(400, false, "No fields to update");
                exit();
            }
            
            $values[] = $data['student_id'];
            $stmt = $db->prepare("UPDATE students SET " . implode(', ', $fields) . " WHERE student_id = ?");
            
            if($stmt->execute($values)) {
                sendResponse(200, true, "Student updated successfully");
            } else {
                sendResponse(500, false, "Failed to update student");
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $check = $db->prepare("SELECT student_id FROM students WHERE student_id = ?");
                $check->execute([$_GET['id']]);
                if(!$check->fetch()) {
                    sendResponse(404, false, "Student not found");
                    exit();
                }

                $query = "DELETE FROM students WHERE student_id = ?";
                $stmt = $db->prepare($query);
                
                if($stmt->execute([$_GET['id']])) {
                    sendResponse(200, true, "Student deleted successfully");
                } else {
                    sendResponse(500, false, "Unable to delete student (check foreign key constraints)");
                }
            } else {
                sendResponse(400, false, "ID required for deletion");
            }
            break;
            
        default:
            sendResponse(405, false, "Method not allowed: " . $method);
            break;
    }
} catch (Exception $e) {
    sendResponse(500, false, "Server error: " . $e->getMessage());
}
?>