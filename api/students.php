<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// Log the request method and incoming data
file_put_contents('debug.log', "Method: " . $method . "\n", FILE_APPEND);
file_put_contents('debug.log', "Raw input: " . file_get_contents("php://input") . "\n", FILE_APPEND);

try {
    switch($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $query = "SELECT * FROM students WHERE student_id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $_GET['id']);
                $stmt->execute();
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($student);
            } else {
                $query = "SELECT * FROM students ORDER BY lastname, firstname";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($students);
            }
            break;
            
        case 'POST':
            // Debug: Log that we're in POST section
            file_put_contents('debug.log', "Entering POST section\n", FILE_APPEND);
            
            $input = file_get_contents("php://input");
            file_put_contents('debug.log', "Input received: " . $input . "\n", FILE_APPEND);
            
            $data = json_decode($input, true);
            file_put_contents('debug.log', "Decoded data: " . print_r($data, true) . "\n", FILE_APPEND);
            
            if (!$data) {
                $error_msg = "No data received or invalid JSON. JSON Error: " . json_last_error_msg();
                file_put_contents('debug.log', $error_msg . "\n", FILE_APPEND);
                echo json_encode(["message" => $error_msg]);
                exit();
            }
            
            // Check required fields
            $required_fields = ['student_number', 'lastname', 'firstname', 'email', 'gender'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                $error_msg = "Missing required fields: " . implode(', ', $missing_fields);
                file_put_contents('debug.log', $error_msg . "\n", FILE_APPEND);
                echo json_encode(["message" => $error_msg]);
                exit();
            }
            
            // Prepare the query
            $query = "INSERT INTO students (student_number, lastname, firstname, middlename, email, gender, year_level, course, contact_number) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            file_put_contents('debug.log', "Query: " . $query . "\n", FILE_APPEND);
            
            $stmt = $db->prepare($query);
            
            $params = [
                $data['student_number'],
                $data['lastname'],
                $data['firstname'],
                $data['middlename'] ?? null,
                $data['email'],
                $data['gender'],
                $data['year_level'] ?? null,
                $data['course'] ?? null,
                $data['contact_number'] ?? null
            ];
            
            file_put_contents('debug.log', "Parameters: " . print_r($params, true) . "\n", FILE_APPEND);
            
            if($stmt->execute($params)) {
                $success_msg = "Student created successfully with ID: " . $db->lastInsertId();
                file_put_contents('debug.log', $success_msg . "\n", FILE_APPEND);
                echo json_encode(["message" => "Student created successfully", "id" => $db->lastInsertId()]);
            } else {
                $error = $stmt->errorInfo();
                $error_msg = "Execute failed: " . $error[2];
                file_put_contents('debug.log', $error_msg . "\n", FILE_APPEND);
                file_put_contents('debug.log', "Full error info: " . print_r($error, true) . "\n", FILE_APPEND);
                echo json_encode(["message" => "Unable to create student: " . $error[2]]);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $query = "DELETE FROM students WHERE student_id = ?";
                $stmt = $db->prepare($query);
                
                if($stmt->execute([$_GET['id']])) {
                    echo json_encode(["message" => "Student deleted successfully"]);
                } else {
                    echo json_encode(["message" => "Unable to delete student"]);
                }
            }
            break;
            
        default:
            echo json_encode(["message" => "Method not allowed: " . $method]);
            break;
    }
} catch (Exception $e) {
    $error_msg = "Exception: " . $e->getMessage();
    file_put_contents('debug.log', $error_msg . "\n", FILE_APPEND);
    echo json_encode(["message" => "Database error: " . $e->getMessage()]);
}
?>