<?php
/**
 * Database Migration Runner
 * Run this ONCE to add assisted renewal columns
 * Access: Admin only
 */

session_start();
require_once 'config.php';
require_once 'db.php';

// Check if user is logged in and has proper role
if (!isset($_SESSION['officer_id']) || !is_role(['secretary', 'president'])) {
    die('Access denied. Secretary/President only.');
}

$pdo = get_pdo();
$migrationFile = '../add_assisted_renewal_columns.sql';
$rollbackFile = '../rollback_assisted_renewal_columns.sql';

// Check if migration file exists
if (!file_exists($migrationFile)) {
    die('Migration file not found: ' . $migrationFile);
}

$action = $_GET['action'] ?? 'check';
$output = [];
$migrationNeeded = false;

// Function to check if migration is needed
function check_migration_status($pdo) {
    try {
        // Check if the new columns exist
        $stmt = $pdo->query("SHOW COLUMNS FROM applications LIKE 'uploaded_by_officer_id'");
        return $stmt->rowCount() === 0; // Returns true if column doesn't exist (migration needed)
    } catch (PDOException $e) {
        return true; // Assume migration needed if check fails
    }
}

// Function to run SQL file
function run_sql_file($pdo, $filepath) {
    $sql = file_get_contents($filepath);
    if ($sql === false) {
        throw new Exception("Could not read file: $filepath");
    }
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $results = [];
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $results[] = ['success' => true, 'statement' => substr($statement, 0, 100) . '...'];
        } catch (PDOException $e) {
            $results[] = ['success' => false, 'statement' => substr($statement, 0, 100) . '...', 'error' => $e->getMessage()];
        }
    }
    
    return $results;
}

// Perform action
if ($action === 'check') {
    $migrationNeeded = check_migration_status($pdo);
    $output[] = $migrationNeeded 
        ? '⚠️ Migration needed: assisted renewal columns are missing' 
        : '✅ Migration already applied: assisted renewal columns exist';
        
} elseif ($action === 'migrate') {
    $migrationNeeded = check_migration_status($pdo);
    
    if (!$migrationNeeded) {
        $output[] = '✅ Migration already applied. No action needed.';
    } else {
        $output[] = '🚀 Running migration...';
        try {
            $results = run_sql_file($pdo, $migrationFile);
            foreach ($results as $result) {
                if ($result['success']) {
                    $output[] = '✅ ' . $result['statement'];
                } else {
                    $output[] = '❌ ' . $result['statement'] . ' - Error: ' . $result['error'];
                }
            }
            $output[] = '<strong>✅ Migration completed successfully!</strong>';
        } catch (Exception $e) {
            $output[] = '<strong>❌ Migration failed: ' . htmlspecialchars($e->getMessage()) . '</strong>';
        }
    }
    
} elseif ($action === 'rollback') {
    if (!file_exists($rollbackFile)) {
        $output[] = '❌ Rollback file not found: ' . $rollbackFile;
    } else {
        $output[] = '⏮️ Running rollback...';
        try {
            $results = run_sql_file($pdo, $rollbackFile);
            foreach ($results as $result) {
                if ($result['success']) {
                    $output[] = '✅ ' . $result['statement'];
                } else {
                    $output[] = '❌ ' . $result['statement'] . ' - Error: ' . $result['error'];
                }
            }
            $output[] = '<strong>✅ Rollback completed successfully!</strong>';
        } catch (Exception $e) {
            $output[] = '<strong>❌ Rollback failed: ' . htmlspecialchars($e->getMessage()) . '</strong>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Assisted Renewal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .status-box {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .status-box.success {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        
        .status-box.warning {
            background: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .output {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .output div {
            margin-bottom: 8px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245, 87, 108, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #555;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
        
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .warning-box strong {
            color: #e65100;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Database Migration</h1>
        <p class="subtitle">Assisted Renewal Feature - Add Required Database Columns</p>
        
        <div class="info-box">
            <h3>What this migration does:</h3>
            <ul>
                <li>Adds <code>uploaded_by_officer_id</code> to applications table</li>
                <li>Adds <code>consent_given</code> to applications table</li>
                <li>Adds <code>is_staff_assisted</code> to applications table</li>
                <li>Adds <code>uploaded_by_officer_id</code> to application_documents table</li>
                <li>Creates foreign key relationships</li>
            </ul>
        </div>
        
        <?php if (!empty($output)): ?>
        <div class="output">
            <?php foreach ($output as $line): ?>
                <div><?php echo $line; ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="warning-box">
            <strong>⚠️ Important:</strong> Run this migration only ONCE. Running it multiple times may cause errors.
        </div>
        
        <div class="btn-group">
            <a href="?action=check" class="btn btn-secondary">
                🔍 Check Status
            </a>
            <a href="?action=migrate" class="btn btn-primary" 
               onclick="return confirm('Are you sure you want to run the migration?')">
                ⬆️ Run Migration
            </a>
            <a href="?action=rollback" class="btn btn-danger" 
               onclick="return confirm('⚠️ WARNING: This will remove the assisted renewal columns! Continue?')">
                ⬇️ Rollback
            </a>
        </div>
        
        <div class="btn-group" style="margin-top: 15px;">
            <a href="dashboard.php" class="btn btn-secondary">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
