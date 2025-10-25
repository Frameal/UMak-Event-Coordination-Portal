<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {
        case 'GET':
            $query = "SELECT er.*, s.student_number, s.firstname, s.lastname, e.event_name, e.event_date
                     FROM event_registrations er
                     JOIN students s ON er.student_id = s.student_id
                     JOIN events e ON er.event_id = e.event_id
                     ORDER BY er.registration_date DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($registrations);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Check if student is already registered for this event
            $check_query = "SELECT COUNT(*) FROM event_registrations WHERE student_id = ? AND event_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$data['student_id'], $data['event_id']]);
            
            if ($check_stmt->fetchColumn() > 0) {
                echo json_encode(["message" => "Student is already registered for this event"]);
                return;
            }
            
            // Generate QR code
            $qr_code = 'QR' . time() . rand(1000, 9999);
            
            $query = "INSERT INTO event_registrations (event_id, student_id, qr_code, status) 
                     VALUES (?, ?, ?, 'Registered')";
            
            $stmt = $db->prepare($query);
            
            if($stmt->execute([
                $data['event_id'],
                $data['student_id'],
                $qr_code
            ])) {
                echo json_encode([
                    "message" => "Registration successful", 
                    "qr_code" => $qr_code,
                    "registration_id" => $db->lastInsertId()
                ]);
            } else {
                echo json_encode(["message" => "Unable to create registration"]);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $query = "DELETE FROM event_registrations WHERE registration_id = ?";
                $stmt = $db->prepare($query);
                
                if($stmt->execute([$_GET['id']])) {
                    echo json_encode(["message" => "Registration cancelled successfully"]);
                } else {
                    echo json_encode(["message" => "Unable to cancel registration"]);
                }
            }
            break;
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>