<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Optional: Filter by Organization ID
$org_id = isset($_GET['org_id']) ? $_GET['org_id'] : null;

if (isset($_GET['event_id'])) {
    // GET DETAILED REPORT FOR ONE EVENT
    $eId = $_GET['event_id'];
    
    $avgSql = "SELECT 
        AVG(obj_clarity) as avg_obj_clarity, AVG(obj_relevance) as avg_obj_relevance,
        AVG(cond_flow) as avg_cond_flow, AVG(cond_facilitators) as avg_cond_facilitators, AVG(cond_activities) as avg_cond_activities,
        AVG(res_mastery) as avg_res_mastery, AVG(res_presentation) as avg_res_presentation, AVG(res_participation) as avg_res_participation,
        AVG(tech_venue) as avg_tech_venue, AVG(tech_schedule) as avg_tech_schedule, AVG(tech_accommodation) as avg_tech_accommodation, AVG(tech_sounds) as avg_tech_sounds
        FROM event_evaluations WHERE event_id = ?";
    $stmtAvg = $db->prepare($avgSql);
    $stmtAvg->execute([$eId]);
    $averages = $stmtAvg->fetch(PDO::FETCH_ASSOC);

    $commSql = "SELECT comments, submitted_at FROM event_evaluations WHERE event_id = ? AND comments IS NOT NULL AND comments != '' ORDER BY submitted_at DESC";
    $stmtComm = $db->prepare($commSql);
    $stmtComm->execute([$eId]);
    $comments = $stmtComm->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "averages" => $averages, "comments" => $comments]);

} else {
    // GET LIST OF EVENTS WITH EVALUATION STATS
    $sql = "SELECT 
                e.event_id, e.event_name, e.event_date, e.attendees_capacity,
                (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'Attended') as total_attended,
                (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND has_evaluated = 1) as total_evaluations
            FROM events e
            WHERE e.status IN ('Completed', 'Ongoing', 'Published')";

    if ($org_id) {
        $sql .= " AND e.created_by_org = ?";
    }
    $sql .= " ORDER BY e.event_date DESC";

    $stmt = $db->prepare($sql);
    if($org_id) $stmt->execute([$org_id]); else $stmt->execute();

    echo json_encode(["success" => true, "events" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
?>