<?php
/**
 * Web-based Migration Runner for Driver-to-Officer Messaging System
 * 
 * This script creates the messaging tables in the database.
 * Run this once via browser, then DELETE this file for security.
 * 
 * URL: http://your-domain.com/run_messaging_migration.php
 */

require_once __DIR__ . '/Admin_BOFETODA/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging Migration Runner</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #16233d;
            margin-top: 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💬 Driver-to-Officer Messaging Migration</h1>
        
        <div class="info">
            <strong>ℹ️ About This Migration</strong><br>
            This will create two new tables:
            <ul>
                <li><code>driver_messages</code> - Stores messages between drivers and officers</li>
                <li><code>message_attachments</code> - Stores file attachments for messages</li>
            </ul>
        </div>

<?php

try {
    $pdo = get_pdo();
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/add_driver_messaging.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read migration file");
    }
    
    // Split into individual statements (separated by semicolons)
    // Remove comments and empty lines first
    $sqlLines = explode("\n", $sql);
    $cleanedSql = '';
    
    foreach ($sqlLines as $line) {
        $line = trim($line);
        // Skip comment lines and empty lines
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        $cleanedSql .= $line . "\n";
    }
    
    // Now split by semicolons
    $statements = array_filter(
        array_map('trim', explode(';', $cleanedSql)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 10; // At least some meaningful content
        }
    );
    
    echo '<h2>📋 Executing Migration...</h2>';
    
    $successCount = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Extract table name from CREATE TABLE statement for display
            if (preg_match('/CREATE TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?(\w+)`?/i', $statement, $matches)) {
                echo '<div class="success">✓ Created table: <strong>' . htmlspecialchars($matches[1]) . '</strong></div>';
            } elseif (preg_match('/DROP TABLE(?:\s+IF\s+EXISTS)?\s+`?(\w+)`?/i', $statement, $matches)) {
                echo '<div class="info">🗑️ Dropped table: <strong>' . htmlspecialchars($matches[1]) . '</strong></div>';
            }
        } catch (PDOException $e) {
            // Check if error is "table already exists" or "unknown table" (for DROP)
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/CREATE TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?(\w+)`?/i', $statement, $matches)) {
                    echo '<div class="info">ℹ️ Table already exists: <strong>' . htmlspecialchars($matches[1]) . '</strong></div>';
                }
            } elseif (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), 'Unknown table') !== false) {
                // Ignore "table doesn't exist" errors for DROP TABLE
                if (preg_match('/DROP TABLE(?:\s+IF\s+EXISTS)?\s+`?(\w+)`?/i', $statement, $matches)) {
                    echo '<div class="info">ℹ️ Table did not exist: <strong>' . htmlspecialchars($matches[1]) . '</strong></div>';
                }
            } else {
                $errors[] = $e->getMessage();
                echo '<div class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
    
    if (empty($errors)) {
        echo '<div class="success">';
        echo '<h3>✅ Migration Completed Successfully!</h3>';
        echo '<p>Executed ' . $successCount . ' statement(s).</p>';
        echo '<p><strong>Next Steps:</strong></p>';
        echo '<ol>';
        echo '<li>Verify tables in your database</li>';
        echo '<li><strong style="color: #dc3545;">DELETE this migration runner file for security!</strong></li>';
        echo '<li>Continue with the implementation</li>';
        echo '</ol>';
        echo '</div>';
        
        echo '<div class="warning">';
        echo '<strong>⚠️ Security Warning</strong><br>';
        echo 'DELETE this file immediately: <code>run_messaging_migration.php</code><br>';
        echo 'Leaving migration files accessible is a security risk.';
        echo '</div>';
        
    } else {
        echo '<div class="error">';
        echo '<h3>⚠️ Migration completed with errors</h3>';
        echo '<p>Some statements failed. Review errors above.</p>';
        echo '</div>';
    }
    
    // Show table verification
    echo '<h2>🔍 Table Verification</h2>';
    
    // First, show current database
    try {
        $stmt = $pdo->query("SELECT DATABASE() as db");
        if ($stmt) {
            $currentDb = $stmt->fetch();
            echo '<div class="info">';
            echo '<strong>Current Database:</strong> ' . htmlspecialchars($currentDb['db'] ?? 'Unknown');
            echo '</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error">Could not determine current database: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // Show all tables in database
    try {
        $stmt = $pdo->query("SHOW TABLES");
        if ($stmt) {
            $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo '<div class="info">';
            echo '<strong>All Tables in Database (' . count($allTables) . '):</strong><br>';
            if (empty($allTables)) {
                echo '<em>No tables found</em>';
            } else {
                echo '<pre>' . htmlspecialchars(implode("\n", $allTables)) . '</pre>';
            }
            echo '</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="error">Could not list tables: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    $tables = ['driver_messages', 'message_attachments'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="success">';
            echo '<strong>✓ Table: ' . htmlspecialchars($table) . '</strong><br>';
            echo 'Columns: ' . count($columns);
            echo '</div>';
            
            echo '<pre>';
            foreach ($columns as $col) {
                echo htmlspecialchars($col['Field']) . ' (' . htmlspecialchars($col['Type']) . ')' . "\n";
            }
            echo '</pre>';
            
        } catch (PDOException $e) {
            echo '<div class="error">✗ Table not found: ' . htmlspecialchars($table) . '</div>';
        }
    }
    
} catch (Exception $e) {
    echo '<div class="error">';
    echo '<h3>❌ Migration Failed</h3>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

?>

    </div>
</body>
</html>
