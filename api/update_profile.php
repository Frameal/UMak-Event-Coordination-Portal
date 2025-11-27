<?php
// File: umak_ecp/api/update_profile.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid Request Method"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$userId = $data['user_id'] ?? null;
$userType = $data['user_type'] ?? null;
$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (!$userId || !$userType || !$currentPassword) {
    echo json_encode(["success" => false, "message" => "Missing required fields (ID, Type, or Current Password)"]);
    exit();
}

// Determine Table and ID Column
$table = '';
$idCol = '';
$passCol = 'password'; // All tables use 'password' column based on SQL

switch ($userType) {
    case 'admin':
        $table = 'admin';
        $idCol = 'admin_id';
        break;
    case 'student':
        $table = 'students';
        $idCol = 'student_id';
        break;
    case 'organization':
        $table = 'organizations';
        $idCol = 'org_id';
        break;
    default:
        echo json_encode(["success" => false, "message" => "Invalid user type"]);
        exit();
}

try {
    // 1. Verify Current Password
    $stmt = $db->prepare("SELECT $passCol FROM $table WHERE $idCol = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit();
    }

    // Check hash (or plain text if legacy)
    if (!password_verify($currentPassword, $user[$passCol]) && $currentPassword !== $user[$passCol]) {
        echo json_encode(["success" => false, "message" => "Incorrect current password"]);
        exit();
    }

    // 2. Update Profile Fields
    $updateFields = [];
    $params = [];

    // Common Fields
    if (!empty($data['email'])) {
        $emailCol = ($userType === 'organization') ? 'org_email' : 'email';
        $updateFields[] = "$emailCol = ?";
        $params[] = $data['email'];
    }

    // Student/Admin Specifics
    if ($userType !== 'organization') {
        if (!empty($data['contact_number'])) {
            $updateFields[] = "contact_number = ?";
            $params[] = $data['contact_number'];
        }
    }
    
    // Org Specifics
    if ($userType === 'organization') {
         if (!empty($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
        }
    }

    // Password Update
    if (!empty($newPassword)) {
        // Strong password check
        if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPassword)) {
             echo json_encode(["success" => false, "message" => "New password too weak. (8+ chars, Upper, Number, Special)"]);
             exit();
        }
        $updateFields[] = "$passCol = ?";
        $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    if (empty($updateFields)) {
        echo json_encode(["success" => true, "message" => "No changes made."]);
        exit();
    }

    $sql = "UPDATE $table SET " . implode(", ", $updateFields) . " WHERE $idCol = ?";
    $params[] = $userId;

    $updateStmt = $db->prepare($sql);
    if ($updateStmt->execute($params)) {
        echo json_encode(["success" => true, "message" => "Profile updated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Update failed."]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>