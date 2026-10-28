<?php
/**
 * Database Migration: Add profile_picture column to officers table
 * 
 * Visit this page in your browser to run the migration:
 * Example: https://yourdomain.com/Admin_BOFETODA/migrate_profile_picture.php
 * 
 * IMPORTANT: Delete this file after running the migration for security!
 */

require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Profile Picture</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #2d3748;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #718096;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .info-box {
            background: #edf2f7;
            border-left: 4px solid #4299e1;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .info-box p {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.6;
        }
        .sql-code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 20px 0;
            overflow-x: auto;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
        }
        .result.success {
            background: #c6f6d5;
            border: 2px solid #48bb78;
            color: #22543d;
        }
        .result.error {
            background: #fed7d7;
            border: 2px solid #f56565;
            color: #742a2a;
        }
        .result h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .result ul {
            margin-left: 20px;
            margin-top: 10px;
        }
        .result li {
            margin: 5px 0;
            font-size: 14px;
        }
        .warning {
            background: #fef5e7;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning strong {
            color: #d68910;
            display: block;
            margin-bottom: 5px;
        }
        .warning p {
            color: #856404;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Migration</h1>
        <p class="subtitle">Add Profile Picture Support for Officers</p>

        <div class="info-box">
            <h3>What this migration does:</h3>
            <p>Adds a <code>profile_picture</code> column to the <code>officers</code> table, allowing admin users to upload and display their profile pictures.</p>
        </div>

        <div class="sql-code">ALTER TABLE officers<br>ADD COLUMN profile_picture VARCHAR(255) NULL AFTER initials;</div>

        <?php
        $migrationRun = false;
        $success = false;
        $message = '';
        $details = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
            $migrationRun = true;
            
            try {
                $pdo = get_pdo();
                
                // Check if column already exists
                $stmt = $pdo->query("SHOW COLUMNS FROM officers LIKE 'profile_picture'");
                $columnExists = $stmt->fetch();
                
                if ($columnExists) {
                    $success = false;
                    $message = 'Column Already Exists';
                    $details[] = 'The profile_picture column already exists in the officers table.';
                    $details[] = 'No migration needed. You can delete this file now.';
                } else {
                    // Run the migration
                    $pdo->exec("ALTER TABLE officers ADD COLUMN profile_picture VARCHAR(255) NULL AFTER initials");
                    
                    // Verify it was added
                    $stmt = $pdo->query("SHOW COLUMNS FROM officers LIKE 'profile_picture'");
                    $columnExists = $stmt->fetch();
                    
                    if ($columnExists) {
                        $success = true;
                        $message = 'Migration Completed Successfully!';
                        $details[] = 'The profile_picture column has been added to the officers table.';
                        $details[] = 'Officers can now upload profile pictures from their profile modal.';
                        $details[] = 'Profile pictures will appear in the sidebar and profile modal.';
                    } else {
                        throw new Exception('Column was not created. Please check database permissions.');
                    }
                }
                
            } catch (PDOException $e) {
                $success = false;
                $message = 'Database Error';
                $details[] = 'Error: ' . $e->getMessage();
                $details[] = 'Please check your database connection and permissions.';
            } catch (Exception $e) {
                $success = false;
                $message = 'Migration Failed';
                $details[] = $e->getMessage();
            }
        }
        ?>

        <?php if ($migrationRun): ?>
            <div class="result <?= $success ? 'success' : 'error' ?>">
                <h3><?= $success ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?></h3>
                <ul>
                    <?php foreach ($details as $detail): ?>
                        <li><?= htmlspecialchars($detail) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($success): ?>
                <div class="warning">
                    <strong>⚠️ Security Notice</strong>
                    <p>Please delete this file (migrate_profile_picture.php) from your server now that the migration is complete.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="run_migration" class="btn">Run Migration Now</button>
            </form>

            <div class="warning" style="margin-top: 20px;">
                <strong>⚠️ Before Running</strong>
                <p>Make sure you have a database backup. This migration will modify the officers table structure.</p>
            </div>
        <?php endif; ?>

        <div class="info-box" style="margin-top: 20px;">
            <h3>After Migration:</h3>
            <p>
                1. Refresh any admin pages<br>
                2. Open the profile modal<br>
                3. Click the 📷 camera icon to upload a picture<br>
                4. Delete this migration file for security
            </p>
        </div>
    </div>
</body>
</html>
