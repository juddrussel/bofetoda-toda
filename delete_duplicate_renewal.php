<?php
/**
 * Delete Duplicate RENEWAL Announcement
 * Run this once to clean up the duplicate renewal period
 */

require_once __DIR__ . '/Admin_BOFETODA/config.php';

$pdo = get_pdo();

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Delete Duplicate Renewal</title></head><body>\n";
echo "<h2>Delete Duplicate Renewal Period</h2>\n";
echo "<pre>\n";

try {
    // List all announcements
    $stmt = $pdo->query(
        "SELECT announcement_id, title, status, opened_date, deadline_date 
         FROM announcements 
         ORDER BY announcement_id DESC"
    );
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Current Announcements ===\n\n";
    foreach ($announcements as $ann) {
        echo "ID: {$ann['announcement_id']}\n";
        echo "Title: {$ann['title']}\n";
        echo "Status: {$ann['status']}\n";
        echo "Opened: {$ann['opened_date']}\n";
        echo "Deadline: {$ann['deadline_date']}\n";
        echo "---\n\n";
    }
    
    // Find the duplicate RENEWAL
    $renewals = array_filter($announcements, function($a) {
        return strtoupper(trim($a['title'])) === 'RENEWAL';
    });
    
    if (count($renewals) > 1) {
        echo "Found " . count($renewals) . " 'RENEWAL' announcements!\n\n";
        
        // Keep the one with the latest opened_date (most recent)
        usort($renewals, function($a, $b) {
            return strtotime($b['opened_date']) - strtotime($a['opened_date']);
        });
        
        $keep = array_shift($renewals); // Keep the most recent
        echo "✓ KEEPING: ID {$keep['announcement_id']} - Opened {$keep['opened_date']}\n\n";
        
        // Delete the rest
        foreach ($renewals as $duplicate) {
            echo "✗ DELETING: ID {$duplicate['announcement_id']} - Opened {$duplicate['opened_date']}\n";
            
            // Delete the announcement
            $deleteStmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = :id");
            $deleteStmt->execute(['id' => $duplicate['announcement_id']]);
            
            echo "  → Deleted successfully!\n";
        }
        
        echo "\n✅ Cleanup complete! Duplicate 'RENEWAL' announcement(s) removed.\n";
    } else {
        echo "✓ No duplicates found. You only have one 'RENEWAL' announcement.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='/Admin_BOFETODA/manage_announcements.php'>← Back to Manage Announcements</a></p>\n";
echo "</body></html>";
