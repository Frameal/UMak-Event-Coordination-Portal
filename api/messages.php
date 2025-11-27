<?php
// File: api/messages.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// --- GET REQUESTS ---
if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : null;

    // 1. FETCH RECIPIENTS FOR DROPDOWN (NEW FEATURE)
    if ($action === 'get_recipients') {
        $type = $_GET['type'] ?? '';
        
        if ($type === 'admin') {
            // Fetch all active admins
            $stmt = $db->query("SELECT admin_id as id, CONCAT(firstname, ' ', lastname, ' (', department, ')') as name FROM admin WHERE is_active = 1");
            echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } 
        elseif ($type === 'organization') {
            // Fetch all active organizations
            $stmt = $db->query("SELECT org_id as id, org_name as name FROM organizations WHERE is_active = 1");
            echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } 
        else {
            echo json_encode(["success" => false, "data" => []]);
        }
        exit();
    }

    $userId = $_GET['user_id'] ?? null;
    $userType = $_GET['user_type'] ?? null;

    if (!$userId || !$userType) {
        echo json_encode(["success" => false, "message" => "User details required"]);
        exit();
    }

    // 2. GET UNREAD COUNT
    if ($action === 'unread_count') {
        $sql = "SELECT COUNT(*) as count FROM messages 
                WHERE receiver_type = ? AND receiver_id = ? AND is_read = 0 AND deleted_by_receiver = 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userType, $userId]);
        echo json_encode(["success" => true, "count" => $stmt->fetch(PDO::FETCH_ASSOC)['count']]);
        exit();
    }

    $contactId = isset($_GET['contact_id']) ? $_GET['contact_id'] : null;
    $contactType = isset($_GET['contact_type']) ? $_GET['contact_type'] : null;

    // 3. FETCH SPECIFIC CONVERSATION
    if ($contactId && $contactType) {
        // Mark as read
        $updateSql = "UPDATE messages SET is_read = 1 
                      WHERE sender_type = ? AND sender_id = ? AND receiver_type = ? AND receiver_id = ?";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute([$contactType, $contactId, $userType, $userId]);

        $sql = "SELECT * FROM messages 
                WHERE (
                    (sender_type = ? AND sender_id = ? AND receiver_type = ? AND receiver_id = ? AND deleted_by_sender = 0)
                    OR 
                    (sender_type = ? AND sender_id = ? AND receiver_type = ? AND receiver_id = ? AND deleted_by_receiver = 0)
                )
                ORDER BY created_at ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $userType, $userId, $contactType, $contactId,
            $contactType, $contactId, $userType, $userId
        ]);
        
        echo json_encode(["success" => true, "messages" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    // 4. FETCH CONTACT LIST
    else {
        $sql = "SELECT DISTINCT 
                    CASE 
                        WHEN sender_type = ? AND sender_id = ? THEN receiver_type
                        ELSE sender_type 
                    END as contact_type,
                    CASE 
                        WHEN sender_type = ? AND sender_id = ? THEN receiver_id
                        ELSE sender_id 
                    END as contact_id,
                    MAX(created_at) as last_msg_time,
                    (SELECT COUNT(*) FROM messages m2 
                     WHERE m2.sender_type = contact_type AND m2.sender_id = contact_id 
                       AND m2.receiver_type = ? AND m2.receiver_id = ? AND m2.is_read = 0) as unread_count
                FROM messages 
                WHERE (sender_type = ? AND sender_id = ? AND deleted_by_sender = 0) 
                   OR (receiver_type = ? AND receiver_id = ? AND deleted_by_receiver = 0)
                GROUP BY contact_type, contact_id
                ORDER BY last_msg_time DESC";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $userType, $userId, $userType, $userId,
            $userType, $userId,
            $userType, $userId, $userType, $userId
        ]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($contacts as &$contact) {
            if($contact['contact_type'] == 'student') {
                $s = $db->prepare("SELECT firstname, lastname, student_number FROM students WHERE student_id = ?");
                $s->execute([$contact['contact_id']]);
                $res = $s->fetch(PDO::FETCH_ASSOC);
                $contact['name'] = $res ? $res['firstname'] . ' ' . $res['lastname'] : 'Unknown Student';
                $contact['sub'] = $res ? $res['student_number'] : '';
            } elseif($contact['contact_type'] == 'organization') {
                $o = $db->prepare("SELECT org_name FROM organizations WHERE org_id = ?");
                $o->execute([$contact['contact_id']]);
                $res = $o->fetch(PDO::FETCH_ASSOC);
                $contact['name'] = $res ? $res['org_name'] : 'Unknown Org';
                $contact['sub'] = 'Organization';
            } elseif($contact['contact_type'] == 'admin') {
                $a = $db->prepare("SELECT firstname, lastname, department FROM admin WHERE admin_id = ?");
                $a->execute([$contact['contact_id']]);
                $res = $a->fetch(PDO::FETCH_ASSOC);
                $contact['name'] = $res ? $res['department'] . ' - ' . $res['lastname'] : 'Admin';
                $contact['sub'] = 'Administrator';
            }
        }

        echo json_encode(["success" => true, "contacts" => $contacts]);
    }
}

// --- POST MESSAGE ---
elseif ($method === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $data = json_decode($inputJSON, true);
    
    if(empty($data)) {
        $data = $_POST;
    }

    if(empty($data['message_body']) && empty($_FILES['attachment'])) {
        echo json_encode(["success" => false, "message" => "Message cannot be empty"]);
        exit();
    }

    $attachmentPath = null;
    $attachmentName = null;
    $attachmentType = null;

    // Handle File Upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/messages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($_FILES['attachment']['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $uniqueName = uniqid('msg_') . '.' . $fileType;
        $targetFile = $uploadDir . $uniqueName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
            $attachmentPath = '../../uploads/messages/' . $uniqueName; 
            $attachmentName = $fileName;
            $attachmentType = $fileType;
        }
    }

    try {
        $sql = "INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, category, subject, message_body, attachment_path, attachment_name, attachment_type, created_at, is_read) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['sender_type'],
            $data['sender_id'],
            $data['receiver_type'],
            $data['receiver_id'],
            $data['category'] ?? 'General',
            $data['subject'] ?? NULL,
            $data['message_body'] ?? '',
            $attachmentPath,
            $attachmentName,
            $attachmentType
        ]);

        echo json_encode(["success" => true, "message" => "Sent"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

// --- DELETE ---
elseif ($method === 'DELETE') {
    $userId = $_GET['user_id'];
    $userType = $_GET['user_type'];
    $contactId = $_GET['contact_id'];
    $contactType = $_GET['contact_type'];

    try {
        $sql1 = "UPDATE messages SET deleted_by_sender = 1 
                 WHERE sender_type = ? AND sender_id = ? AND receiver_type = ? AND receiver_id = ?";
        $stmt1 = $db->prepare($sql1);
        $stmt1->execute([$userType, $userId, $contactType, $contactId]);

        $sql2 = "UPDATE messages SET deleted_by_receiver = 1 
                 WHERE receiver_type = ? AND receiver_id = ? AND sender_type = ? AND sender_id = ?";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute([$userType, $userId, $contactType, $contactId]);

        echo json_encode(["success" => true, "message" => "Conversation deleted"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>