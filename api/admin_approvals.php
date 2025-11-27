<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = $_GET['type'] ?? '';
    
    if ($type === 'admin') {
        $dept = $_GET['dept'] ?? '';
        if (empty($dept)) {
            echo json_encode([]);
            exit();
        }
        // Fetch admins pending approval for the specific department
        $query = "SELECT * FROM admin WHERE approval_status = 'Pending' AND department = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$dept]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($type === 'org') {
        // Fetch orgs pending approval (CSOA sees all)
        $query = "SELECT * FROM organizations WHERE approval_status = 'Pending'";
        $stmt = $db->query($query);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    else {
        echo json_encode([]);
    }
}

elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $type = $data['type'] ?? '';
    $id = $data['id'] ?? '';
    $status = $data['status'] ?? ''; // 'Approved' or 'Rejected'

    if (empty($type) || empty($id) || empty($status)) {
        echo json_encode(["success" => false, "message" => "Missing parameters"]);
        exit();
    }

    $table = ($type === 'admin') ? 'admin' : 'organizations';
    $idCol = ($type === 'admin') ? 'admin_id' : 'org_id';

    try {
        $query = "UPDATE $table SET approval_status = ? WHERE $idCol = ?";
        
        // If rejected, we might want to set is_active = 0 or delete.
        // For now, just updating status. Login check already handles 'Rejected'.
        
        $stmt = $db->prepare($query);
        if ($stmt->execute([$status, $id])) {
            echo json_encode(["success" => true, "message" => "Status updated"]);
        } else {
            echo json_encode(["success" => false, "message" => "Update failed"]);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>