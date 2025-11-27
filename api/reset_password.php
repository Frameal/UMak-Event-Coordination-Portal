<?php
// File: api/reset_password.php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

$otpInput = $data['otp'] ?? '';
$newPass = $data['password'] ?? '';

// Retrieve session data
$sessionOtp = $_SESSION['reset_otp'] ?? '';
$email = $_SESSION['reset_email'] ?? '';
$userType = $_SESSION['reset_user_type'] ?? '';

if (empty($otpInput) || empty($newPass)) {
    echo json_encode(["success" => false, "message" => "OTP and New Password are required."]);
    exit();
}

// Strong Password Check
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $newPass)) {
    echo json_encode(["success" => false, "message" => "Password too weak. Must be 8+ characters, contain uppercase, number, and special char."]);
    exit();
}

if (empty($sessionOtp) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Session expired. Please request a new code."]);
    exit();
}

if ($otpInput != $sessionOtp) {
    echo json_encode(["success" => false, "message" => "Invalid OTP Code."]);
    exit();
}

// 1. Determine Table
$tableMap = ['student' => 'students', 'admin' => 'admin', 'organization' => 'organizations'];
$emailColMap = ['student' => 'email', 'admin' => 'email', 'organization' => 'org_email'];

if (!isset($tableMap[$userType])) {
    echo json_encode(["success" => false, "message" => "Invalid user type session."]);
    exit();
}

$table = $tableMap[$userType];
$col = $emailColMap[$userType];

// 2. Update Password
$hashed = password_hash($newPass, PASSWORD_DEFAULT);
$upd = $db->prepare("UPDATE $table SET password = ? WHERE $col = ?");

if ($upd->execute([$hashed, $email])) {
    unset($_SESSION['reset_otp']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_user_type']);
    
    echo json_encode(["success" => true, "message" => "Password successfully updated! You can now login."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update password in database."]);
}
?>