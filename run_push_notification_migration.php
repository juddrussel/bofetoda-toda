<?php
/**
 * Run Push Notification Migration
 * Execute this file once to set up push notification tables
 */

require_once __DIR__ . '/Admin_BOFETODA/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Push Notification Migration</title></head><body>\n";
echo "<h2>Push Notification Migration</h2>\n";
echo "<pre>\n";

try {
    $pdo = get_pdo();
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/add_push_notifications.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Failed to read migration file");
    }
    
    echo "Executing push notification migration...\n\n";
    
    // Execute the SQL statements
    $pdo->exec($sql);
    
    echo "✓ Migration executed successfully!\n\n";
    
    // Verify tables were created
    echo "Verifying table creation...\n";
    
    $tables = ['push_subscriptions', 'reminder_log', 'notifications'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
            
            // Show column structure
            $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            echo "  Columns: ";
            echo implode(', ', array_column($columns, 'Field')) . "\n";
        } else {
            echo "✗ Table '$table' NOT FOUND\n";
        }
    }
    
    echo "\n--- Migration Complete ---\n";
    echo "Push notification system tables are ready!\n";
    echo "\nNext steps:\n";
    echo "1. Generate VAPID keys (run generate_vapid_keys.php)\n";
    echo "2. Configure environment variables\n";
    echo "3. Delete this migration file for security\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='Admin_BOFETODA/dashboard.php'>← Back to Dashboard</a></p>\n";
echo "</body></html>";
