<?php
/**
 * API endpoint to get unread message count for officers
 * Returns JSON with count
 */

require_once __DIR__ . '/config.php';

// Verify officer is logged in
try {
    require_login();
    
    // Get unread message count
    $count = get_unread_message_count();
    
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
