<?php
/**
 * BOFETODA Database Restore Script
 * Run this file after resetting the database to restore all accounts
 * Access: https://your-domain.com/run_restore.php
 */

// Security: Only allow access with password
$RESTORE_PASSWORD = 'restore123';

if (!isset($_POST['password']) && !isset($_GET['password'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>BOFETODA - Restore Accounts</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #1e3a8a;
                margin-bottom: 10px;
            }
            .warning {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 15px;
                margin: 20px 0;
            }
            .info {
                background: #dbeafe;
                border-left: 4px solid #3b82f6;
                padding: 15px;
                margin: 20px 0;
            }
            input[type="password"] {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 16px;
                box-sizing: border-box;
            }
            button {
                background: #1e3a8a;
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 4px;
                font-size: 16px;
                cursor: pointer;
                margin-top: 10px;
                width: 100%;
            }
            button:hover {
                background: #1e40af;
            }
            ul {
                line-height: 1.8;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔄 Restore BOFETODA Accounts</h1>
            
            <div class="warning">
                <strong>⚠️ Warning:</strong> This will restore all officer and driver accounts from the backup.
            </div>
            
            <div class="info">
                <strong>📋 What will be restored:</strong>
                <ul>
                    <li>2 Officer accounts (President & Secretary)</li>
                    <li>130 Driver accounts with barangay-based IDs</li>
                </ul>
            </div>
            
            <form method="POST">
                <label for="password"><strong>Enter Restore Password:</strong></label><br><br>
                <input type="password" name="password" id="password" placeholder="Enter password" required>
                <button type="submit">🚀 Restore Accounts</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Check password
$password = $_POST['password'] ?? $_GET['password'] ?? '';
if ($password !== $RESTORE_PASSWORD) {
    die('<h1 style="color:red;">❌ Incorrect Password</h1><a href="run_restore.php">Try Again</a>');
}

// Database connection
require_once __DIR__ . '/Admin_BOFETODA/db.php';

try {
    $pdo = get_pdo();
} catch (Exception $e) {
    die('<h1 style="color:red;">❌ Database Connection Error: ' . htmlspecialchars($e->getMessage()) . '</h1>');
}

// Read and execute the SQL file
$sqlFile = __DIR__ . '/restore_accounts_only.sql';

if (!file_exists($sqlFile)) {
    die('<h1 style="color:red;">❌ Error: restore_accounts_only.sql file not found!</h1>');
}

echo '<!DOCTYPE html>
<html>
<head>
    <title>BOFETODA - Restore Progress</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #1e3a8a; }
        .success { color: #059669; padding: 10px; margin: 10px 0; background: #d1fae5; border-radius: 4px; }
        .error { color: #dc2626; padding: 10px; margin: 10px 0; background: #fee2e2; border-radius: 4px; }
        .info { color: #0369a1; padding: 10px; margin: 10px 0; background: #e0f2fe; border-radius: 4px; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .summary { background: #f0fdf4; border: 2px solid #059669; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .summary h2 { color: #059669; margin-top: 0; }
        a.button {
            display: inline-block;
            background: #1e3a8a;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Restoring BOFETODA Accounts...</h1>';

// Read SQL file content
$sql = file_get_contents($sqlFile);

if ($sql === false) {
    echo '<div class="error">❌ Failed to read SQL file.</div>';
    exit;
}

echo '<div class="info">📄 SQL file loaded successfully.</div>';

// Split SQL into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               !preg_match('/^\/\*/', $stmt);
    }
);

echo '<div class="info">📊 Found ' . count($statements) . ' SQL statements to execute.</div>';

// Execute each statement
$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    // Don't display USE statements
    if (stripos($statement, 'USE ') === 0) {
        continue;
    }
    
    try {
        $pdo->exec($statement);
        $successCount++;
        
        // Show progress for INSERT statements
        if (stripos($statement, 'INSERT INTO officers') !== false) {
            echo '<div class="success">✅ Officers restored</div>';
        } elseif (stripos($statement, 'INSERT INTO drivers') !== false) {
            echo '<div class="success">✅ Drivers restored</div>';
        }
    } catch (PDOException $e) {
        $errorCount++;
        $error = $e->getMessage();
        $errors[] = "Statement " . ($index + 1) . ": " . $error;
        echo '<div class="error">❌ Error: ' . htmlspecialchars($error) . '</div>';
    }
}

// Final summary
echo '<div class="summary">';
echo '<h2>📊 Restore Summary</h2>';
echo '<p><strong>Total Statements Executed:</strong> ' . ($successCount + $errorCount) . '</p>';
echo '<p><strong>Successful:</strong> <span style="color:#059669;">' . $successCount . '</span></p>';
echo '<p><strong>Errors:</strong> <span style="color:#dc2626;">' . $errorCount . '</span></p>';

// Get counts from database
$officerCount = $pdo->query("SELECT COUNT(*) as cnt FROM officers")->fetchColumn();
$driverCount = $pdo->query("SELECT COUNT(*) as cnt FROM drivers")->fetchColumn();

echo '<hr style="margin: 20px 0;">';
echo '<p><strong>👔 Officers in Database:</strong> ' . $officerCount . '</p>';
echo '<p><strong>🚗 Drivers in Database:</strong> ' . $driverCount . '</p>';

if ($officerCount == 2 && $driverCount == 130) {
    echo '<h3 style="color:#059669;">✅ All accounts restored successfully!</h3>';
} else {
    echo '<h3 style="color:#dc2626;">⚠️ Account count mismatch - please verify!</h3>';
}

echo '</div>';

// Show sample accounts
echo '<h3>🔑 Sample Login Credentials</h3>';
echo '<pre>';
echo "Officer Login:\n";
echo "  Username: OFF-20240001\n";
echo "  Password: president123\n\n";
echo "Driver Login:\n";
echo "  Username: DRV-NT0072\n";
echo "  Password: bienvenido64\n";
echo '</pre>';

if (!empty($errors)) {
    echo '<h3 style="color:#dc2626;">❌ Errors Encountered:</h3>';
    echo '<pre style="background:#fee2e2;color:#dc2626;">';
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . "\n";
    }
    echo '</pre>';
}

echo '<a href="../Admin_BOFETODA/login.php" class="button">🔐 Go to Admin Login</a> ';
echo '<a href="../Driver_BOFETODA/driver-login.php" class="button">🚗 Go to Driver Login</a>';

echo '</div></body></html>';
?>
