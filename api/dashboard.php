<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

$stats = [];

try {
    // Total students
    $query = "SELECT COUNT(*) as total FROM students";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total events
    $query = "SELECT COUNT(*) as total FROM events";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total venues
    $query = "SELECT COUNT(*) as total FROM venues WHERE is_available = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_venues'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total registrations
    $query = "SELECT COUNT(*) as total FROM event_registrations WHERE status = 'Registered'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Upcoming events
    $query = "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE() AND status IN ('Published', 'Registration Open')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['upcoming_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent registrations (last 7 days)
    $query = "SELECT COUNT(*) as total FROM event_registrations WHERE registration_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['recent_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode($stats);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>