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

// Set content type for browser display
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Import</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .section { margin: 10px 0; }
    </style>
</head>
<body>
<h1>🗄️ BOFETODA Database Import Script</h1>
<?php
echo "<div class='section'>";

try {
    // Connect to Aiven MySQL
    echo "<strong>1. Connecting to Aiven MySQL...</strong><br>";
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    echo "<span class='success'>✓ Connected successfully!</span><br><br>";

    // Read SQL file
    echo "<strong>2. Reading SQL file...</strong><br>";
    $sqlFile = __DIR__ . '/database_schema.sql';
    
    if (!file_exists($sqlFile)) {
        die("<span class='error'>✗ Error: database_schema.sql not found!</span><br>");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "<span class='success'>✓ SQL file loaded (" . strlen($sql) . " bytes)</span><br><br>";

    // Split SQL into individual statements
    echo "<strong>3. Executing SQL statements...</strong><br>";
    flush(); // Force output to browser
    
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
                echo "→ Creating database 'bofetoda'...<br>";
            } elseif (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "→ Creating table '<strong>{$tableName}</strong>'...<br>";
            } elseif (stripos($statement, 'INSERT') !== false) {
                preg_match('/INSERT\s+(?:IGNORE\s+)?INTO\s+(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "→ Inserting data into '<strong>{$tableName}</strong>'...<br>";
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "→ Altering table '<strong>{$tableName}</strong>'...<br>";
            } elseif (stripos($statement, 'UPDATE') !== false) {
                echo "→ Updating data...<br>";
            }
            flush(); // Show progress in real-time
            
            $pdo->exec($statement);
            $executed++;
            
        } catch (PDOException $e) {
            // Some errors are expected (like "database already exists")
            if (stripos($e->getMessage(), 'already exists') !== false) {
                echo "<span class='warning'>⚠ Warning: " . htmlspecialchars($e->getMessage()) . " (skipping)</span><br>";
            } else {
                echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
                $errors++;
            }
        }
    }

    echo "<br><strong>4. Import Summary:</strong><br>";
    echo "<span class='success'>✓ Executed: {$executed} statements</span><br>";
    if ($errors > 0) {
        echo "<span class='warning'>⚠ Errors: {$errors}</span><br>";
    }
    
    // Verify tables were created
    echo "<br><strong>5. Verifying database...</strong><br>";
    $pdo->exec("USE bofetoda");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<span class='success'>✓ Found " . count($tables) . " tables:</span><br>";
    foreach ($tables as $table) {
        echo "&nbsp;&nbsp;• {$table}<br>";
    }
    
    // Count drivers
    $driverCount = $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
    echo "<br><span class='success'>✓ Drivers in database: {$driverCount}</span><br>";
    
    // Count officers
    $officerCount = $pdo->query("SELECT COUNT(*) FROM officers")->fetchColumn();
    echo "<span class='success'>✓ Officers in database: {$officerCount}</span><br>";

    echo "<br><h2 class='success'>✅ Import Complete!</h2>";
    echo "<p>Your database is ready to use!</p>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠ IMPORTANT:</strong> Update your Render environment variable:<br>";
    echo "&nbsp;&nbsp;<code>DB_NAME = bofetoda</code> (change from 'defaultdb')<br>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<br><span class='error'><strong>✗ Fatal Error:</strong> " . htmlspecialchars($e->getMessage()) . "</span><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    exit(1);
}
echo "</div></body></html>";
?>
