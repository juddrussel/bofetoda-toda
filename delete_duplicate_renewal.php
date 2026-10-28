<?php
/**
 * Reset All Renewal Announcements
 * Run this once to delete all announcements and start fresh
 */

require_once __DIR__ . '/Admin_BOFETODA/config.php';

$pdo = get_pdo();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Reset Renewal List</title></head><body>\n";
echo "<h2>Reset All Renewal Announcements</h2>\n";
echo "<pre>\n";

try {
    // Show current announcements
    $stmt = $pdo->query(
        "SELECT announcement_id, title, status, opened_date, deadline_date 
         FROM announcements 
         ORDER BY announcement_id DESC"
    );
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Current Announcements (Before Reset) ===\n\n";
    foreach ($announcements as $ann) {
        echo "ID: {$ann['announcement_id']} - {$ann['title']} ({$ann['status']}) - {$ann['opened_date']} to {$ann['deadline_date']}\n";
    }
    
    echo "\n=== DELETING ALL ANNOUNCEMENTS ===\n\n";
    
    // Delete all announcements
    $deleteCount = $pdo->exec("DELETE FROM announcements");
    
    echo "✅ Deleted $deleteCount announcement(s)\n\n";
    
    // Reset driver renewal statuses
    $resetDrivers = $pdo->exec("UPDATE drivers SET renewal_status = '-' WHERE renewal_status != '-'");
    
    echo "✅ Reset $resetDrivers driver(s) renewal status\n\n";
    
    echo "=== RESET COMPLETE ===\n\n";
    echo "All announcements have been deleted.\n";
    echo "All drivers' renewal statuses have been reset.\n";
    echo "You can now create a fresh renewal period from the admin panel.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='/Admin_BOFETODA/manage_announcements.php'>← Go to Manage Announcements</a></p>\n";
echo "<p><strong>⚠️ IMPORTANT:</strong> Delete this file after running it for security!</p>\n";
echo "</body></html>";
