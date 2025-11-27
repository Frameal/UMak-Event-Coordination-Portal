<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET LIST OF PENDING AND COMPLETED EVALUATIONS
    $student_id = $_GET['student_id'];
    
    $pendingSql = "SELECT er.registration_id, e.event_id, e.event_name, e.event_date, e.banner_image 
                   FROM event_registrations er
                   JOIN events e ON er.event_id = e.event_id
                   WHERE er.student_id = ? AND er.status = 'Attended' AND er.has_evaluated = 0 AND e.requires_evaluation = 1";
    
    $completedSql = "SELECT er.registration_id, e.event_id, e.event_name, e.event_date, e.banner_image, ev.submitted_at
                     FROM event_registrations er
                     JOIN events e ON er.event_id = e.event_id
                     JOIN event_evaluations ev ON er.registration_id = ev.registration_id
                     WHERE er.student_id = ? AND er.has_evaluated = 1";

    $stmtP = $db->prepare($pendingSql); $stmtP->execute([$student_id]);
    $stmtC = $db->prepare($completedSql); $stmtC->execute([$student_id]);

    echo json_encode([
        "success" => true,
        "pending" => $stmtP->fetchAll(PDO::FETCH_ASSOC),
        "completed" => $stmtC->fetchAll(PDO::FETCH_ASSOC)
    ]);

} elseif ($method === 'POST') {
    // SUBMIT NEW EVALUATION
    $data = json_decode(file_get_contents("php://input"), true);

    try {
        $db->beginTransaction();

        $sql = "INSERT INTO event_evaluations 
                (registration_id, event_id, student_id, 
                 obj_clarity, obj_relevance, 
                 cond_flow, cond_facilitators, cond_activities,
                 res_mastery, res_presentation, res_participation,
                 tech_venue, tech_schedule, tech_accommodation, tech_sounds,
                 comments)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['registration_id'], $data['event_id'], $data['student_id'],
            $data['obj_clarity'], $data['obj_relevance'],
            $data['cond_flow'], $data['cond_facilitators'], $data['cond_activities'],
            $data['res_mastery'], $data['res_presentation'], $data['res_participation'],
            $data['tech_venue'], $data['tech_schedule'], $data['tech_accommodation'], $data['tech_sounds'],
            $data['comments']
        ]);

        // Mark as evaluated
        $updateSql = "UPDATE event_registrations SET has_evaluated = 1 WHERE registration_id = ?";
        $db->prepare($updateSql)->execute([$data['registration_id']]);

        $db->commit();
        echo json_encode(["success" => true, "message" => "Evaluation submitted successfully."]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}
?>