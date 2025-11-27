<?php
// File: api/forgot_password.php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

include_once '../config/database.php';
include_once '../config/mail_config.php';

$db = (new Database())->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$userType = $data['user_type'] ?? '';

if (empty($email) || empty($userType)) {
    echo json_encode(["success" => false, "message" => "Email and User Type required."]);
    exit();
}

// 1. Check if user exists in correct table
$tableMap = ['student' => 'students', 'admin' => 'admin', 'organization' => 'organizations'];
$emailColMap = ['student' => 'email', 'admin' => 'email', 'organization' => 'org_email'];

if (!array_key_exists($userType, $tableMap)) {
    echo json_encode(["success" => false, "message" => "Invalid user type."]);
    exit();
}

$table = $tableMap[$userType];
$col = $emailColMap[$userType];

$stmt = $db->prepare("SELECT * FROM $table WHERE $col = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    // 2. Generate 6-digit OTP instead of token link
    $otp = rand(100000, 999999);
    
    // Store OTP in session (and optionally DB)
    $_SESSION['reset_otp'] = $otp;
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_user_type'] = $userType;

    // 3. Send OTP Email
    $body = "
        <h3 style='color: #022d6d;'>Password Reset Request</h3>
        <p>We received a request to reset your password for your UMak ECP ($userType) account.</p>
        <p>Your Reset Code is:</p>
        <div style='background-color: #f0f4f8; padding: 15px; border-radius: 4px; margin: 20px 0; text-align: center;'>
            <h1 style='color: #f36b00; letter-spacing: 5px; margin: 0;'>" . $otp . "</h1>
        </div>
        <p>Use this code to set a new password. Do not share it with anyone.</p>
    ";

    if (sendEmail($email, "UMak ECP - Reset Code", $body)) {
        echo json_encode(["success" => true, "message" => "Reset code sent to your email."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to send email."]);
    }
} else {
    // Security: Don't reveal if email exists or not, but say sent to avoid enumeration
    echo json_encode(["success" => true, "message" => "If the email exists, a reset code has been sent."]);
}
?>