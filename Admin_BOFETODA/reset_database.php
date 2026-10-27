<?php
/**
 * Database Content Reset Script
 * 
 * WARNING: This will DELETE ALL DATA from the database tables
 * Tables structures will be preserved, only data will be removed
 * 
 * Usage: Navigate to this file in your browser
 * Security: Protected by confirmation parameter
 */

require_once __DIR__ . '/config.php';

// Simple password protection - no admin login required
$authorized = false;

if (isset($_POST['reset_password'])) {
    // Check password (change this to your preferred password)
    if ($_POST['reset_password'] === 'reset123') {
        $_SESSION['reset_authorized'] = true;
        $authorized = true;
    } else {
        $error = 'Incorrect password!';
    }
}

if (isset($_SESSION['reset_authorized']) && $_SESSION['reset_authorized'] === true) {
    $authorized = true;
}

// Show login form if not authorized
if (!$authorized) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Reset - Authentication</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 40px;
                max-width: 400px;
                width: 100%;
            }
            h1 { color: #16233d; margin-bottom: 20px; font-size: 24px; }
            .form-group { margin: 20px 0; }
            label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; }
            input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                font-size: 16px;
            }
            .btn {
                width: 100%;
                padding: 12px;
                background: #16233d;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
            }
            .btn:hover { background: #0f1729; }
            .error {
                background: #fee2e2;
                color: #991b1b;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 20px;
            }
            .info {
                background: #e0e7ff;
                color: #3730a3;
                padding: 12px;
                border-radius: 6px;
                margin-top: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔒 Database Reset - Authentication</h1>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="reset_password">Enter Reset Password:</label>
                    <input type="password" name="reset_password" id="reset_password" required autofocus>
                </div>
                <button type="submit" class="btn">Authenticate</button>
            </form>
            <div class="info">
                <strong>Default password:</strong> reset123<br>
                Change this in the PHP file for security.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$executed = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['understand'])) {
    try {
        $pdo = get_pdo();
        
        // Disable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        // List of tables to truncate
        $tables = [
            'applications',
            'application_documents',
            'notifications',
            'driver_messages',
            'message_attachments',
            'drivers',
            'announcements',
            'announcement_images',
            'renewal_cycles',
            'access_logs',
            'backup_logs'
        ];
        
        foreach ($tables as $table) {
            try {
                $pdo->exec("TRUNCATE TABLE `$table`");
                $results[] = "✓ Truncated table: $table";
            } catch (PDOException $e) {
                $results[] = "✗ Failed to truncate $table: " . $e->getMessage();
            }
        }
        
        // Re-enable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        // Optional: Create default admin account
        // Uncomment below if you want to restore a default admin
        /*
        $stmt = $pdo->prepare("
            INSERT INTO officers (officer_id, name, role, password_hash, created_at) 
            VALUES (:id, :name, :role, :pass, NOW())
        ");
        $stmt->execute([
            'id' => 'ADMIN-001',
            'name' => 'System Administrator',
            'role' => 'admin',
            'pass' => password_hash('admin123', PASSWORD_DEFAULT)
        ]);
        $results[] = "✓ Created default admin account (ID: ADMIN-001, Password: admin123)";
        */
        
        $executed = true;
        $results[] = "\n✓ DATABASE RESET COMPLETED SUCCESSFULLY!";
        
    } catch (Exception $e) {
        $results[] = "✗ ERROR: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Reset - BOFETODA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #16233d;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .warning {
            background: #fee2e2;
            border: 2px solid #dc2626;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: #991b1b;
        }
        .warning h2 {
            color: #dc2626;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .warning ul {
            margin-left: 20px;
            margin-top: 10px;
        }
        .warning li {
            margin: 5px 0;
        }
        .form-group {
            margin: 20px 0;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .checkbox-group label {
            cursor: pointer;
            font-weight: 600;
        }
        .btn {
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
        .btn-danger:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .results {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
        }
        .results p {
            margin: 5px 0;
            line-height: 1.6;
        }
        .success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }
        .info {
            background: #e0e7ff;
            border: 1px solid #6366f1;
            color: #3730a3;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Database Content Reset</h1>
        
        <?php if ($executed): ?>
            <div class="results success">
                <h2 style="color: #065f46; margin-bottom: 15px;">Reset Complete!</h2>
                <?php foreach ($results as $result): ?>
                    <p><?= htmlspecialchars($result) ?></p>
                <?php endforeach; ?>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
            <a href="reset_database.php" class="btn btn-secondary">Reset Again</a>
        <?php else: ?>
            <div class="warning">
                <h2>⚠️ WARNING - DESTRUCTIVE ACTION</h2>
                <p><strong>This will permanently delete ALL data from the database!</strong></p>
                <ul>
                    <li>All driver records</li>
                    <li>All applications and documents</li>
                    <li>All messages and attachments</li>
                    <li>All announcements</li>
                    <li>All access logs</li>
                    <li>All officer accounts (except yours)</li>
                </ul>
                <p style="margin-top: 15px;"><strong>Table structures will be preserved.</strong></p>
                <p><strong>This action cannot be undone!</strong></p>
            </div>
            
            <div class="info">
                <strong>Before proceeding:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Make sure you have a backup</li>
                    <li>Inform all users about the reset</li>
                    <li>Double-check this is what you want to do</li>
                </ul>
            </div>
            
            <form method="POST" action="" id="resetForm">
                <div class="checkbox-group">
                    <input type="checkbox" id="understand" name="understand" required>
                    <label for="understand">I understand this will delete all data</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="backup" name="backup" required>
                    <label for="backup">I have backed up the database</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="confirm" name="confirm" required>
                    <label for="confirm">I want to proceed with the reset</label>
                </div>
                
                <div class="form-group" style="margin-top: 30px;">
                    <button type="submit" class="btn btn-danger" id="submitBtn" disabled>
                        Reset Database
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <script>
                const checkboxes = document.querySelectorAll('input[type="checkbox"]');
                const submitBtn = document.getElementById('submitBtn');
                
                checkboxes.forEach(cb => {
                    cb.addEventListener('change', function() {
                        const allChecked = Array.from(checkboxes).every(c => c.checked);
                        submitBtn.disabled = !allChecked;
                    });
                });
                
                document.getElementById('resetForm').addEventListener('submit', function(e) {
                    if (!confirm('ARE YOU ABSOLUTELY SURE? This will delete ALL data!')) {
                        e.preventDefault();
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
