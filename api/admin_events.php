<?php
// File: umak_ecp/api/admin_events.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

include_once '../config/database.php';
include_once 'update_status_helper.php';

$database = new Database();
$db = $database->getConnection();

// Run automatic status updates
updateEventStatuses($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

// --- GET REQUESTS ---
if ($method === 'GET') {

    // 0. FETCH SINGLE EVENT DETAILS
    if (!empty($id)) {
        try {
            $query = "SELECT e.*, v.venue_name, o.org_name 
                      FROM events e
                      LEFT JOIN venues v ON e.venue_id = v.venue_id
                      LEFT JOIN organizations o ON e.created_by_org = o.org_id
                      WHERE e.event_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                echo json_encode(["success" => false, "message" => "Event not found"]);
                exit();
            }

            $regQuery = "SELECT r.*, s.student_number, s.firstname, s.lastname, s.college, s.course, s.year_level, s.section
                         FROM event_registrations r
                         LEFT JOIN students s ON r.student_id = s.student_id
                         WHERE r.event_id = ?";
            $regStmt = $db->prepare($regQuery);
            $regStmt->execute([$id]);
            $registrations = $regStmt->fetchAll(PDO::FETCH_ASSOC);

            $event['registrations'] = $registrations;
            echo json_encode(["success" => true, "data" => $event]);
            exit();
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit();
        }
    }
    
    // 1. CALENDAR DATA
    if ($action === 'calendar') {
        $query = "SELECT 
                    e.event_id, 
                    e.event_name as title, 
                    e.event_date as start, 
                    CONCAT(e.event_date, 'T', e.start_time) as start_time, 
                    CONCAT(e.event_date, 'T', e.end_time) as end_time, 
                    e.status, 
                    e.venue_id,
                    v.venue_name,
                    e.target_college,
                    e.attendees_capacity,
                    e.event_type
                  FROM events e
                  LEFT JOIN venues v ON e.venue_id = v.venue_id
                  WHERE e.status NOT IN ('Draft', 'Cancelled', 'Rejected')"; 
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($events as &$event) {
            $event['start'] = $event['start_time'];
            $event['end'] = $event['end_time'];
            switch($event['status']) {
                case 'Published': $event['color'] = '#007bff'; break;
                case 'Registration Open': $event['color'] = '#28a745'; break;
                case 'Registration Closed': $event['color'] = '#dc3545'; break;
                case 'Ongoing': $event['color'] = '#ffc107'; $event['textColor'] = '#000'; break;
                case 'Completed': $event['color'] = '#6c757d'; break;
                case 'Pending Approval': $event['color'] = '#17a2b8'; break;
                default: $event['color'] = '#6c757d';
            }
        }
        echo json_encode($events);
        exit();
    }

    // 2. PENDING APPROVALS
    if ($action === 'pending') {
        $dept = isset($_GET['department']) ? $_GET['department'] : '';
        if(empty($dept)) { echo json_encode([]); exit(); }

        if ($dept === 'CSOA') {
            $query = "SELECT e.*, v.venue_name, o.org_name 
                      FROM events e
                      LEFT JOIN venues v ON e.venue_id = v.venue_id
                      LEFT JOIN organizations o ON e.created_by_org = o.org_id
                      LEFT JOIN event_approvals a ON e.event_id = a.event_id AND a.department = 'CSOA'
                      WHERE (e.status = 'Draft' OR e.status = 'Pending Approval') 
                      AND e.approval_step = 1
                      AND (a.status IS NULL OR a.status = 'Pending')";
        } 
        elseif ($dept === 'UFMO') {
            $query = "SELECT e.*, v.venue_name, o.org_name 
                      FROM events e
                      LEFT JOIN venues v ON e.venue_id = v.venue_id
                      LEFT JOIN organizations o ON e.created_by_org = o.org_id
                      LEFT JOIN event_approvals a ON e.event_id = a.event_id AND a.department = 'UFMO'
                      WHERE e.approval_step = 2
                      AND (a.status IS NULL OR a.status = 'Pending')";
        } 
        else {
            $query = "SELECT e.*, v.venue_name, o.org_name, r.* FROM events e
                      LEFT JOIN venues v ON e.venue_id = v.venue_id
                      LEFT JOIN organizations o ON e.created_by_org = o.org_id
                      LEFT JOIN event_requirements r ON e.event_id = r.event_id
                      JOIN event_approvals a ON e.event_id = a.event_id
                      WHERE e.approval_step = 3 
                      AND a.department = ? 
                      AND a.status = 'Pending'"; 
        }

        $stmt = $db->prepare($query);
        if ($dept !== 'CSOA' && $dept !== 'UFMO') {
            $stmt->execute([$dept]);
        } else {
            $stmt->execute();
        }
        
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }

    // 3. ALL EVENTS (For Admin List View)
    if ($action === 'all') {
        try {
            $query = "SELECT e.event_id, e.event_name, e.event_date, e.start_time, e.end_time, e.status, e.event_type,
                             v.venue_name, o.org_name 
                      FROM events e
                      LEFT JOIN venues v ON e.venue_id = v.venue_id
                      LEFT JOIN organizations o ON e.created_by_org = o.org_id
                      ORDER BY e.event_date DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            echo json_encode(["success" => true, "events" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit();
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit();
        }
    }
}

// --- PUT REQUESTS ---
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $eventId = $data['event_id'];

    if (isset($data['status']) && !isset($data['department'])) {
        try {
            $stmt = $db->prepare("UPDATE events SET status = ? WHERE event_id = ?");
            if ($stmt->execute([$data['status'], $eventId])) {
                echo json_encode(["success" => true, "message" => "Event status updated"]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update status"]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
        exit();
    }

    $dept = $data['department']; 
    $status = $data['status']; 
    $remarks = $data['remarks'] ?? '';

    try {
        $db->beginTransaction();

        // Update specific department approval
        $stmt = $db->prepare("UPDATE event_approvals SET status = ?, remarks = ? WHERE event_id = ? AND department = ?");
        $stmt->execute([$status, $remarks, $eventId, $dept]);

        if ($status === 'Approved') {
            if ($dept === 'CSOA') {
                $db->prepare("UPDATE events SET approval_step = 2 WHERE event_id = ?")->execute([$eventId]);
            } 
            elseif ($dept === 'UFMO') {
                $db->prepare("UPDATE events SET approval_step = 3 WHERE event_id = ?")->execute([$eventId]);
            }
            checkAndPublish($db, $eventId);
        }

        if ($status === 'Rejected') {
            $db->prepare("UPDATE events SET status = 'Cancelled', approval_step = 0 WHERE event_id = ?")->execute([$eventId]);
        }

        $db->commit();
        echo json_encode(["success" => true, "message" => "Action processed successfully"]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

function checkAndPublish($db, $eventId) {
    $sql = "SELECT COUNT(*) as pending_count FROM event_approvals 
            WHERE event_id = ? AND status != 'Approved'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$eventId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['pending_count'] == 0) {
        $db->prepare("UPDATE events SET status = 'Published' WHERE event_id = ?")->execute([$eventId]);
    }
}
?>