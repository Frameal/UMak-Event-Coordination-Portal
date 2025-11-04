<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/database.php';

$db = (new Database())->getConnection();

if ($db === null) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

$queries = [
    'total_students' => "SELECT COUNT(*) as total FROM students",
    'total_events' => "SELECT COUNT(*) as total FROM events",
    'total_venues' => "SELECT COUNT(*) as total FROM venues WHERE is_available = 1",
    'total_registrations' => "SELECT COUNT(*) as total FROM event_registrations WHERE status = 'Registered'",
    'upcoming_events' => "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE() AND status IN ('Published', 'Registration Open')",
    'recent_registrations' => "SELECT COUNT(*) as total FROM event_registrations WHERE registration_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
];

try {
    $stats = [];
    foreach ($queries as $key => $query) {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    echo json_encode($stats);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>

