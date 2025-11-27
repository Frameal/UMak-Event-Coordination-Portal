<?php
// File: umak_ecp/api/update_status_helper.php

function updateEventStatuses($db) {
    // 1. SET TIMEZONE TO MANILA
    date_default_timezone_set('Asia/Manila');
    
    try {
        $now = date('Y-m-d H:i:s');

        // 2. Published -> Registration Open
        // Explicitly check if current time is past reg start
        $sql1 = "UPDATE events SET status = 'Registration Open' 
                 WHERE status = 'Published' 
                 AND registration_start <= '$now' 
                 AND (registration_end > '$now' OR registration_end IS NULL)";
        $db->query($sql1);

        // 3. Registration Open -> Registration Closed
        // If current time is past reg end
        $sql2 = "UPDATE events SET status = 'Registration Closed' 
                 WHERE status = 'Registration Open' 
                 AND registration_end <= '$now'";
        $db->query($sql2);

        // 4. Any Pre-Event Status -> Ongoing
        // If current time is within event start/end time
        $sql3 = "UPDATE events SET status = 'Ongoing' 
                 WHERE status IN ('Published', 'Registration Open', 'Registration Closed') 
                 AND CONCAT(event_date, ' ', start_time) <= '$now' 
                 AND CONCAT(event_date, ' ', end_time) >= '$now'";
        $db->query($sql3);

        // 5. Ongoing -> Completed
        // If current time is past event end time
        $sql4 = "UPDATE events SET status = 'Completed' 
                 WHERE status = 'Ongoing' 
                 AND CONCAT(event_date, ' ', end_time) < '$now'";
        $db->query($sql4);

    } catch (Exception $e) {
        // Fail silently as this is a background task
        // error_log($e->getMessage());
    }
}
?>