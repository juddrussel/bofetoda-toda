<?php
/**
 * Simple Migration Runner
 * Visit: https://bofetoda-app.onrender.com/run_migration.php
 * This will run the security questions migration
 */

// Include database connection
require_once 'Admin_BOFETODA/db.php';

// Get PDO connection
$conn = get_pdo();

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Migration Runner</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .warning { color: orange; background: #fff3e0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 Database Migration Runner</h1>
    <p>This will add security question columns to your database.</p>
";

try {
    // Check if already run
    $checkOfficers = $conn->query("SHOW COLUMNS FROM officers LIKE 'security_question_id'");
    $checkDrivers = $conn->query("SHOW COLUMNS FROM drivers LIKE 'security_question_id'");
    
    $officersExists = $checkOfficers->rowCount() > 0;
    $driversExists = $checkDrivers->rowCount() > 0;
    
    if ($officersExists && $driversExists) {
        echo "<div class='warning'><strong>⚠️ Already Migrated</strong><br>";
        echo "The security question columns already exist in both tables.<br>";
        echo "No changes needed.</div>";
    } else {
        echo "<div class='info'><strong>📋 Migration Status:</strong><br>";
        echo "Officers table: " . ($officersExists ? "✅ Already has columns" : "❌ Needs migration") . "<br>";
        echo "Drivers table: " . ($driversExists ? "✅ Already has columns" : "❌ Needs migration") . "</div>";
        
        // Run migrations
        $success = true;
        
        // Migrate officers table
        if (!$officersExists) {
            echo "<div class='info'>Running migration for officers table...</div>";
            $sql = "ALTER TABLE officers 
                    ADD COLUMN security_question_id TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'ID of the chosen security question (1-5)',
                    ADD COLUMN security_answer_hash VARCHAR(255) NULL DEFAULT NULL COMMENT 'Hashed security answer (case-insensitive)',
                    ADD COLUMN failed_recovery_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of failed password recovery attempts',
                    ADD COLUMN recovery_cooldown_until DATETIME NULL DEFAULT NULL COMMENT 'Cooldown expiry timestamp after failed attempts'";
            
            if ($conn->exec($sql) !== false) {
                echo "<div class='success'>✅ Officers table migrated successfully!</div>";
            } else {
                echo "<div class='error'>❌ Failed to migrate officers table</div>";
                $success = false;
            }
        }
        
        // Migrate drivers table
        if (!$driversExists) {
            echo "<div class='info'>Running migration for drivers table...</div>";
            $sql = "ALTER TABLE drivers 
                    ADD COLUMN security_question_id TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'ID of the chosen security question (1-5)',
                    ADD COLUMN security_answer_hash VARCHAR(255) NULL DEFAULT NULL COMMENT 'Hashed security answer (case-insensitive)',
                    ADD COLUMN failed_recovery_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of failed password recovery attempts',
                    ADD COLUMN recovery_cooldown_until DATETIME NULL DEFAULT NULL COMMENT 'Cooldown expiry timestamp after failed attempts'";
            
            if ($conn->exec($sql) !== false) {
                echo "<div class='success'>✅ Drivers table migrated successfully!</div>";
            } else {
                echo "<div class='error'>❌ Failed to migrate drivers table</div>";
                $success = false;
            }
        }
        
        if ($success) {
            echo "<div class='success'><strong>🎉 Migration Complete!</strong><br>";
            echo "All security question columns have been added successfully.<br>";
            echo "You can now use the Forgot Password feature.</div>";
        }
    }
    
    // Show table structure
    echo "<h2>📊 Current Table Structure</h2>";
    
    echo "<h3>Officers Table:</h3><pre>";
    $result = $conn->query("DESCRIBE officers");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-30s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
    }
    echo "</pre>";
    
    echo "<h3>Drivers Table:</h3><pre>";
    $result = $conn->query("DESCRIBE drivers");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-30s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
    }
    echo "</pre>";
    
    echo "<div class='info'><strong>🔒 Security Questions Available:</strong><br>";
    echo "1. What is your mother's maiden name?<br>";
    echo "2. What city were you born in?<br>";
    echo "3. What is the name of your first pet?<br>";
    echo "4. What was your childhood nickname?<br>";
    echo "5. What is your favorite color?</div>";
    
    echo "<div class='warning'><strong>⚠️ Important:</strong><br>";
    echo "Delete this file (run_migration.php) after running the migration for security reasons.</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>❌ Database Error:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
