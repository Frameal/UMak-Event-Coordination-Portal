<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['orgName']) || empty($data['orgEmail']) || empty($data['password']) || empty($data['college'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
        exit();
    }

    // Strong Password Check
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $data['password'])) {
        echo json_encode(["success" => false, "message" => "Password too weak. Must be 8+ chars, 1 Upper, 1 Number, 1 Special."]);
        exit();
    }

    try {
        $checkSql = "SELECT org_id FROM organizations WHERE org_email = ? OR org_name = ?";
        $stmtCheck = $db->prepare($checkSql);
        $stmtCheck->execute([$data['orgEmail'], $data['orgName']]);

        if ($stmtCheck->rowCount() > 0) {
            echo json_encode(["success" => false, "message" => "Organization Name or Email already registered."]);
            exit();
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO organizations (college, org_name, org_email, description, password, is_active, approval_status, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, 'Pending', NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $data['college'],
            $data['orgName'],
            $data['orgEmail'],
            $data['description'] ?? '',
            $hashedPassword
        ]);

        if ($result) {
            echo json_encode(["success" => true, "message" => "Organization registered successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Failed to register organization."]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>