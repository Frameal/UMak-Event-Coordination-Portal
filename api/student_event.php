<?php
// File: umak_ecp/api/student_event.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// Include database configuration
include_once '../config/database.php';
include_once 'update_status_helper.php'; // Ensure statuses are fresh

// Create database connection
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode([
        "success" => false, 
        "message" => "Database connection failed"
    ]);
    exit();
}

// Run status updates to ensure 'Registration Open' / 'Closed' is accurate based on time
updateEventStatuses($db);

// Get student_id from query parameter
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;

if (!$student_id) {
    echo json_encode([
        "success" => false,
        "message" => "Student ID is required"
    ]);
    exit();
}

try {
    // Query to get ALL PUBLISHED/OPEN events
    // Added registration_start, registration_end, attendees_capacity, current_attendees
    $query = "SELECT 
        e.event_id,
        e.event_name,
        e.description,
        e.event_date,
        e.start_time,
        e.end_time,
        e.status,
        e.event_type,
        e.target_college,
        e.event_prioritization,
        e.banner_image,
        e.registration_start,
        e.registration_end,
        e.attendees_capacity,
        e.current_attendees,
        v.venue_name,
        v.location,
        COALESCE(o.org_name, 'University of Makati') as organization_name,
        er.registration_id,
        er.qr_code,
        CASE 
            WHEN er.registration_id IS NOT NULL THEN 'Registered'
            ELSE 'Not Registered'
        END as registration_status
    FROM events e
    LEFT JOIN venues v ON e.venue_id = v.venue_id
    LEFT JOIN organizations o ON e.created_by_org = o.org_id
    LEFT JOIN event_registrations er ON e.event_id = er.event_id AND er.student_id = :student_id
    WHERE e.status IN ('Published', 'Registration Open', 'Registration Closed', 'Ongoing', 'Completed')
    ORDER BY e.event_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true,
        "events" => $events,
        "total" => count($events)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>