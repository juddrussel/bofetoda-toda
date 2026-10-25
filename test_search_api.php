<?php
/**
 * Test Search API - Debug helper
 * DELETE AFTER TESTING
 */

require_once 'Admin_BOFETODA/config.php';
require_once 'Admin_BOFETODA/db.php';

echo "<h1>Search API Test</h1>";
echo "<pre>";

$query = "Maria";

echo "Testing search for: '$query'\n\n";

try {
    $pdo = get_pdo();
    echo "✅ Database connected\n\n";
    
    // Test the search function
    $results = search_drivers_for_assisted_renewal($query, 10);
    
    echo "Results count: " . count($results) . "\n\n";
    
    if (count($results) > 0) {
        echo "✅ Found drivers:\n";
        foreach ($results as $driver) {
            echo "---\n";
            echo "ID: {$driver['driver_id']}\n";
            echo "Name: {$driver['name']}\n";
            echo "Body No: {$driver['body_no']}\n";
            echo "Barangay: {$driver['barangay']}\n";
            echo "Archived: {$driver['is_archived']}\n";
            echo "Has Pending: {$driver['has_pending_app']}\n";
        }
    } else {
        echo "❌ No results found\n\n";
        
        // Check if driver exists at all
        $stmt = $pdo->prepare("SELECT driver_id, name, is_archived FROM drivers WHERE name LIKE ?");
        $stmt->execute(['%Maria%']);
        $allMaria = $stmt->fetchAll();
        
        echo "Direct DB check for 'Maria': " . count($allMaria) . " found\n";
        foreach ($allMaria as $d) {
            echo "  - {$d['driver_id']}: {$d['name']} (archived: {$d['is_archived']})\n";
        }
        
        // Check for open announcement
        $stmt = $pdo->query("SELECT announcement_id, title, status FROM announcements WHERE status = 'OPEN'");
        $openAnn = $stmt->fetchAll();
        echo "\nOpen announcements: " . count($openAnn) . "\n";
        foreach ($openAnn as $a) {
            echo "  - {$a['announcement_id']}: {$a['title']} ({$a['status']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
