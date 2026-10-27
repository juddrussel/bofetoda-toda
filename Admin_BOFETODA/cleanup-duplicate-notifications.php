<?php
/**
 * Cleanup Duplicate Notifications
 * 
 * This script removes duplicate notification records for the same driver/app_no/title combination,
 * keeping only the most recent one.
 */

require_once __DIR__ . '/db.php';

try {
    $pdo = get_pdo();
    
    // Find and delete duplicate notifications, keeping the most recent one for each driver/app_no/title combo
    $sql = "
        DELETE n1 FROM notifications n1
        INNER JOIN notifications n2 
        WHERE n1.driver_id = n2.driver_id
          AND n1.app_no = n2.app_no
          AND n1.title = n2.title
          AND n1.id < n2.id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $deletedCount = $stmt->rowCount();
    
    echo "✓ Cleanup complete!\n";
    echo "Removed {$deletedCount} duplicate notification(s).\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
