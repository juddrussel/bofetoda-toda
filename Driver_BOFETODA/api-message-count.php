<?php
/**
 * API endpoint to get unread reply count for driver
 * Returns JSON with count
 */

require 'driver-database.php';

// Verify driver is logged in
try {
    $driver = require_driver();
    $driver_id = $driver['id'];
    
    // Get unread reply count
    $count = get_my_unread_reply_count($driver_id);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
}
