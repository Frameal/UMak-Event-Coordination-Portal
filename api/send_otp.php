<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

// Include the mail config we just created
include_once '../config/database.php';
include_once '../config/mail_config.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

// Basic validation
if (empty($email) || !strpos($email, '@umak.edu.ph')) {
    echo json_encode(["success" => false, "message" => "Please enter a valid UMak email address."]);
    exit();
}

// Generate 6-digit OTP
$otp = rand(100000, 999999);

// Store OTP in session
$_SESSION['signup_otp'] = $otp;
$_SESSION['signup_email'] = $email;

$body = "
    <h3 style='color: #022d6d;'>Email Verification</h3>
    <p>You are registering for a Student Account at the Event Coordination Portal.</p>
    <p>Your One-Time Password (OTP) is:</p>
    <h1 style='color: #f36b00; letter-spacing: 5px; text-align: center;'>" . $otp . "</h1>
    <p>This code is valid for this session. Do not share this code with anyone.</p>
";

if (sendEmail($email, "UMak ECP - Verification Code", $body)) {
    echo json_encode(["success" => true, "message" => "OTP sent to your email."]);
} else {
    echo json_encode(["success" => false, "message" => "Connection Error: Could not send email. Please check your internet."]);
}
?>