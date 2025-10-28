<?php
// api/login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for CORS and JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
include_once '../config/database.php';

// Check if database file exists
if (!file_exists('../config/database.php')) {
    echo json_encode([
        "success" => false, 
        "message" => "Database configuration file not found. Please config database"
    ]);
    exit();
}

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Check database connection
if ($db === null) {
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed. Check database credentials"
    ]);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false, 
        "message" => "Only POST method is allowed"
    ]);
    exit();
}

// Get POST data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validate input data
if (!$data || empty($data['username']) || empty($data['password']) || empty($data['user_type'])) {
    echo json_encode([
        "success" => false, 
        "message" => "Username, password, and user type are required"
    ]);
    exit();
}

// Sanitize input
$username = trim($data['username']);
$password = trim($data['password']);
$user_type = trim($data['user_type']);

// Validate user type
if (!in_array($user_type, ['admin', 'student'])) {
    echo json_encode([
        "success" => false, 
        "message" => "Invalid user type"
    ]);
    exit();
}

try {
    // Prepare query based on user type
    if ($user_type === 'admin') {
        $query = "SELECT 
            'admin' as user_type,
            admin_id as user_id,
            employee_number as username,
            password,
            firstname,
            lastname,
            email,
            department,
            position
        FROM admin 
        WHERE employee_number = :username
        LIMIT 1";
    } else {
        $query = "SELECT 
            'student' as user_type,
            student_id as user_id,
            student_number as username,
            password,
            firstname,
            lastname,
            email,
            course as department,
            CONCAT(COALESCE(year_level, ''), ' Year') as position
        FROM students 
        WHERE student_number = :username
        LIMIT 1";
    }
    
    // Prepare and execute statement
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    // Fetch user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists and password matches
    if ($user && $user['password'] === $password) {
        // Remove password from response for security
        unset($user['password']);
        
        // Return success response with user data
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "user" => $user
        ]);
    } else {
        // Return error if credentials don't match
        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password. Please check your credentials."
        ]);
    }
    
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Handle other errors
    echo json_encode([
        "success" => false,
        "message" => "Authentication error: " . $e->getMessage()
    ]);
}
?>