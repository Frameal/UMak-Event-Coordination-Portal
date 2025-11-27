<?php
// File: umak_ecp/api/org_evaluations.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$orgId = isset($_GET['org_id']) ? $_GET['org_id'] : null;
$eventId = isset($_GET['event_id']) ? $_GET['event_id'] : null;

if (!$orgId && !$eventId) {
    echo json_encode(["success" => false, "message" => "Organization ID or Event ID required"]);
    exit();
}

try {
    // MODE 1: GET DETAILED REPORT FOR A SINGLE EVENT
    if ($eventId) {
        // 1. Verify ownership (if org_id is provided)
        if ($orgId) {
            $verify = $db->prepare("SELECT event_id FROM events WHERE event_id = ? AND created_by_org = ?");
            $verify->execute([$eventId, $orgId]);
            if ($verify->rowCount() == 0) {
                echo json_encode(["success" => false, "message" => "Access denied"]);
                exit();
            }
        }

        // 2. Calculate Averages for each criteria
        $sqlAvg = "SELECT 
            COUNT(*) as response_count,
            AVG(obj_clarity) as avg_obj_clarity, 
            AVG(obj_relevance) as avg_obj_relevance,
            AVG(cond_flow) as avg_cond_flow, 
            AVG(cond_facilitators) as avg_cond_facilitators, 
            AVG(cond_activities) as avg_cond_activities,
            AVG(res_mastery) as avg_res_mastery, 
            AVG(res_presentation) as avg_res_presentation, 
            AVG(res_participation) as avg_res_participation,
            AVG(tech_venue) as avg_tech_venue, 
            AVG(tech_schedule) as avg_tech_schedule, 
            AVG(tech_accommodation) as avg_tech_accommodation, 
            AVG(tech_sounds) as avg_tech_sounds
            FROM event_evaluations 
            WHERE event_id = ?";
            
        $stmtAvg = $db->prepare($sqlAvg);
        $stmtAvg->execute([$eventId]);
        $stats = $stmtAvg->fetch(PDO::FETCH_ASSOC);

        // 3. Fetch Comments
        $sqlComm = "SELECT comments, submitted_at, s.student_number 
                    FROM event_evaluations ev
                    LEFT JOIN students s ON ev.student_id = s.student_id
                    WHERE ev.event_id = ? AND comments IS NOT NULL AND comments != ''
                    ORDER BY ev.submitted_at DESC";
        $stmtComm = $db->prepare($sqlComm);
        $stmtComm->execute([$eventId]);
        $comments = $stmtComm->fetchAll(PDO::FETCH_ASSOC);

        // 4. Get Event Basic Info for the Header
        $sqlInfo = "SELECT event_name, event_date FROM events WHERE event_id = ?";
        $stmtInfo = $db->prepare($sqlInfo);
        $stmtInfo->execute([$eventId]);
        $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        // 5. Get Total Attendees (for response rate)
        $sqlAtt = "SELECT COUNT(*) as total_attended FROM event_registrations WHERE event_id = ? AND status = 'Attended'";
        $stmtAtt = $db->prepare($sqlAtt);
        $stmtAtt->execute([$eventId]);
        $attended = $stmtAtt->fetch(PDO::FETCH_ASSOC)['total_attended'];

        echo json_encode([
            "success" => true,
            "event_info" => $info,
            "stats" => $stats,
            "comments" => $comments,
            "total_attendees" => $attended
        ]);
        exit();
    }

    // MODE 2: LIST ALL EVENTS FOR ORGANIZATION
    if ($orgId) {
        // Fetch events with counts
        // We calculate a rough "Overall Rating" by averaging the averages of the 4 main categories if needed, 
        // but for list view, let's just get response counts first.
        $query = "SELECT 
                    e.event_id, 
                    e.event_name, 
                    e.event_date, 
                    e.status,
                    (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.event_id AND er.status = 'Attended') as total_attendees,
                    (SELECT COUNT(*) FROM event_evaluations ev WHERE ev.event_id = e.event_id) as total_evaluations,
                    -- Simple average of all columns for a quick 'Overall Rating' snapshot
                    (
                        SELECT AVG(
                            (obj_clarity + obj_relevance + cond_flow + cond_facilitators + cond_activities + 
                             res_mastery + res_presentation + res_participation + 
                             tech_venue + tech_schedule + tech_accommodation + tech_sounds) / 12
                        )
                        FROM event_evaluations ev WHERE ev.event_id = e.event_id
                    ) as overall_rating
                  FROM events e
                  WHERE e.created_by_org = ? AND e.status IN ('Completed', 'Ongoing', 'Published')
                  ORDER BY e.event_date DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([$orgId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success" => true,
            "events" => $events
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>