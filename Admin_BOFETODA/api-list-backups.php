<?php
/**
 * List R2 Backups API
 * Returns list of automated backups with download URLs
 * Access: President only
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/r2-storage.php';

require_role('president');

header('Content-Type: application/json');

try {
    // Check if R2 is available
    if (!is_r2_enabled()) {
        echo json_encode([
            'success' => false,
            'error' => 'R2 storage not configured',
            'backups' => []
        ]);
        exit;
    }
    
    // Get list of backups from R2
    $backups = list_r2_backups();
    
    echo json_encode([
        'success' => true,
        'backups' => $backups,
        'count' => count($backups)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to list backups: ' . $e->getMessage(),
        'backups' => []
    ]);
    
    error_log('Failed to list backups: ' . $e->getMessage());
}
