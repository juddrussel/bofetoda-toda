<?php
/**
 * Database Import Script
 * This script imports the BOFETODA database schema directly to Aiven MySQL
 * Run this once to set up your database
 */

// Database Connection from Environment Variables
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: 3306;
$dbname = 'defaultdb'; // We'll create 'bofetoda' database in the script
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

echo "=== BOFETODA Database Import Script ===\n\n";

try {
    // Connect to Aiven MySQL
    echo "1. Connecting to Aiven MySQL...\n";
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    echo "   ✓ Connected successfully!\n\n";

    // Read SQL file
    echo "2. Reading SQL file...\n";
    $sqlFile = __DIR__ . '/database_schema.sql';
    
    if (!file_exists($sqlFile)) {
        die("   ✗ Error: database_schema.sql not found!\n   Please create the file first.\n");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "   ✓ SQL file loaded (" . strlen($sql) . " bytes)\n\n";

    // Split SQL into individual statements
    echo "3. Executing SQL statements...\n";
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt);
        }
    );

    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty($statement)) continue;
        
        try {
            // Show progress
            if (stripos($statement, 'CREATE DATABASE') !== false) {
                echo "   → Creating database 'bofetoda'...\n";
            } elseif (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "   → Creating table '{$tableName}'...\n";
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "   → Inserting data into '{$tableName}'...\n";
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "   → Altering table '{$tableName}'...\n";
            } elseif (stripos($statement, 'UPDATE') !== false) {
                echo "   → Updating data...\n";
            }
            
            $pdo->exec($statement);
            $executed++;
            
        } catch (PDOException $e) {
            // Some errors are expected (like "database already exists")
            if (stripos($e->getMessage(), 'already exists') !== false) {
                echo "   ⚠ Warning: " . $e->getMessage() . " (skipping)\n";
            } else {
                echo "   ✗ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }

    echo "\n4. Import Summary:\n";
    echo "   ✓ Executed: {$executed} statements\n";
    if ($errors > 0) {
        echo "   ⚠ Errors: {$errors}\n";
    }
    
    // Verify tables were created
    echo "\n5. Verifying database...\n";
    $pdo->exec("USE bofetoda");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "   ✓ Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "     - {$table}\n";
    }
    
    // Count drivers
    $driverCount = $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
    echo "\n   ✓ Drivers in database: {$driverCount}\n";
    
    // Count officers
    $officerCount = $pdo->query("SELECT COUNT(*) FROM officers")->fetchColumn();
    echo "   ✓ Officers in database: {$officerCount}\n";

    echo "\n=== Import Complete! ===\n";
    echo "Your database is ready to use!\n\n";
    
    echo "⚠ IMPORTANT: Update your Render environment variable:\n";
    echo "   DB_NAME = bofetoda (change from 'defaultdb')\n\n";

} catch (PDOException $e) {
    echo "\n✗ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
