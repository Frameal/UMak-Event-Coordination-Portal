<?php
// File: umak_ecp/api/login.php
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit();
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || empty($data['username']) || empty($data['password']) || empty($data['user_type'])) {
    echo json_encode(["success" => false, "message" => "Username, password, and user type are required"]);
    exit();
}

$username = trim($data['username']);
$password = trim($data['password']);
$user_type = trim($data['user_type']);

try {
    $query = "";
    // 1. ADMIN QUERY - Prepared Statement
    if ($user_type === 'admin') {
        $query = "SELECT * FROM admin WHERE employee_number = :username AND is_active = 1 LIMIT 1";
    } 
    // 2. STUDENT QUERY - Prepared Statement
    else if ($user_type === 'student') {
        $query = "SELECT * FROM students WHERE student_number = :username AND is_active = 1 LIMIT 1";
    } 
    // 3. ORGANIZATION QUERY - Prepared Statement
    else if ($user_type === 'organization') {
        $query = "SELECT * FROM organizations WHERE org_email = :username AND is_active = 1 LIMIT 1";
    } else {
        echo json_encode(["success" => false, "message" => "Invalid user type selected"]);
        exit();
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // CHECK APPROVAL STATUS (Only for Admin and Org)
        if (($user_type === 'admin' || $user_type === 'organization')) {
            if (isset($user['approval_status']) && $user['approval_status'] === 'Pending') {
                echo json_encode(["success" => false, "message" => "Account pending approval from Administrator."]);
                exit();
            }
             if (isset($user['approval_status']) && $user['approval_status'] === 'Rejected') {
                echo json_encode(["success" => false, "message" => "Account application was rejected."]);
                exit();
            }
        }

        $passwordMatch = false;
        
        // 1. Check Hash (BCRYPT) - Standard Secure Method
        if (password_verify($password, $user['password'])) {
            $passwordMatch = true;
        } 
        // 2. Fallback: Plain Text (Only for legacy/test data, remove in production)
        else if ($user['password'] === $password) {
            $passwordMatch = true;
        }

        if ($passwordMatch) {
            unset($user['password']); // Remove password from response
            
            // Normalize user_id for frontend session consistency
            if($user_type === 'admin') $user['user_id'] = $user['admin_id'];
            if($user_type === 'student') $user['user_id'] = $user['student_id'];
            if($user_type === 'organization') $user['user_id'] = $user['org_id'];

            // Explicitly set user_type in response
            $user['user_type'] = $user_type;

            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "user" => $user
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect password."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Account not found or inactive."]);
    }
    
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>