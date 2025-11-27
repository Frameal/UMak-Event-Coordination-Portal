<?php
// File: api/admin_dashboard.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Adjust this path to point to your actual database configuration file
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// SQL Queries to get stats (Removed recent_registrations)
$queries = [
    'total_students' => "SELECT COUNT(*) as total FROM students",
    'total_events' => "SELECT COUNT(*) as total FROM events",
    'total_venues' => "SELECT COUNT(*) as total FROM venues WHERE is_available = 1",
    'total_registrations' => "SELECT COUNT(*) as total FROM event_registrations WHERE status = 'Registered'",
    'upcoming_events' => "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE() AND status IN ('Published', 'Registration Open')"
];

try {
    $stats = [];
    foreach ($queries as $key => $query) {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats[$key] = $result['total'];
    }

    echo json_encode([
        "success" => true,
        "data" => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>