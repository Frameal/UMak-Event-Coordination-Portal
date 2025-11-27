<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include_once '../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (isset($_GET['venue_id'])) {
        $venue_id = intval($_GET['venue_id']);
        
        $stmt = $pdo->prepare("
            SELECT venue_id, venue_name, capacity, location, amenities, description, is_available, image_url
            FROM venues
            WHERE venue_id = :venue_id AND is_available = 1
        ");
        
        $stmt->bindParam(':venue_id', $venue_id, PDO::PARAM_INT);
        $stmt->execute();
        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($venue) {
            echo json_encode(['success' => true, 'venue' => $venue], JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(['success' => false, 'message' => 'Venue not found']);
        }
    } else {
        // Added image_url here to fix the missing images issue
        $stmt = $pdo->query("
            SELECT venue_id, venue_name, capacity, location, amenities, description, is_available, image_url
            FROM venues
            WHERE is_available = 1
            ORDER BY venue_name ASC
        ");
        
        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'venues' => $venues, 'total' => count($venues)], JSON_UNESCAPED_SLASHES);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>