<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

// Strong Password Check
function isStrong($pass) {
    return preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($data['firstname']) || empty($data['lastname']) || empty($data['password']) || empty($data['employee_number'])) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit();
    }

    if (!isStrong($data['password'])) {
        echo json_encode(["success" => false, "message" => "Password too weak. Must be 8+ chars, 1 Upper, 1 Number, 1 Special."]);
        exit();
    }

    $email = strtolower($data['firstname'] . '.' . $data['lastname'] . '@umak.edu.ph');
    $email = str_replace(' ', '', $email); 

    $password = password_hash($data['password'], PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO admin (employee_number, lastname, firstname, middlename, password, email, gender, position, department, contact_number, approval_status, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 1)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['employee_number'], 
            $data['lastname'], 
            $data['firstname'], 
            $data['middleinitial'] ?? '',
            $password, 
            $email, 
            $data['gender'], 
            $data['position'], 
            $data['department'], 
            $data['contact_number']
        ]);

        echo json_encode(["success" => true, "message" => "Admin account created! Please wait for approval."]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(["success" => false, "message" => "Employee Number or Email already exists."]);
        } else {
            echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}
?>