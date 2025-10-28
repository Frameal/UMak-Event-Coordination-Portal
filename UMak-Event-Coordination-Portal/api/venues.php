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
            $query = "SELECT * FROM venues ORDER BY venue_name";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($venues);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            
            $query = "INSERT INTO venues (venue_name, capacity, location, facilities) 
                     VALUES (?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            
            if($stmt->execute([
                $data['venue_name'],
                $data['capacity'],
                $data['location'] ?? null,
                $data['facilities'] ?? null
            ])) {
                echo json_encode(["message" => "Venue created successfully", "id" => $db->lastInsertId()]);
            } else {
                echo json_encode(["message" => "Unable to create venue"]);
            }
            break;
            
        case 'DELETE':
            if (isset($_GET['id'])) {
                $query = "DELETE FROM venues WHERE venue_id = ?";
                $stmt = $db->prepare($query);
                
                if($stmt->execute([$_GET['id']])) {
                    echo json_encode(["message" => "Venue deleted successfully"]);
                } else {
                    echo json_encode(["message" => "Unable to delete venue"]);
                }
            }
            break;
    }
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>