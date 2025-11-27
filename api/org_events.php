<?php
// File: api/org_events.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, DELETE");

include_once '../config/database.php';
include_once 'update_status_helper.php';

$database = new Database();
$db = $database->getConnection();

// Run status updates on load
updateEventStatuses($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check for org_id
    if (!isset($_GET['org_id'])) {
        echo json_encode(["success" => false, "message" => "Organization ID required"]);
        exit();
    }

    $orgId = $_GET['org_id'];

    try {
        // Fetch events with venue names
        // Changed query to include Cancelled events if needed, but the instruction says 
        // "reflect in the history... as an event that they made but is cancelled"
        // So we remove "AND e.status != 'Cancelled'"
        $query = "SELECT 
                    e.*, 
                    v.venue_name
                  FROM events e
                  LEFT JOIN venues v ON e.venue_id = v.venue_id
                  WHERE e.created_by_org = ?
                  ORDER BY e.event_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$orgId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "events" => $events]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
} 

elseif ($method === 'DELETE') {
    // Delete an event
    if (!isset($_GET['event_id'])) {
        echo json_encode(["success" => false, "message" => "Event ID required"]);
        exit();
    }

    $eventId = $_GET['event_id'];

    try {
        // Mark as Cancelled instead of deleting to preserve history
        $stmt = $db->prepare("UPDATE events SET status = 'Cancelled' WHERE event_id = ?");
        $result = $stmt->execute([$eventId]);

        if ($result) {
            echo json_encode(["success" => true, "message" => "Event cancelled successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to cancel event"]);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}
?>