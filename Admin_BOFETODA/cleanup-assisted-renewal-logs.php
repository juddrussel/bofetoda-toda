<?php
/**
 * Cleanup Script: Remove application numbers from Assisted Renewal log entries
 * 
 * This script updates all activity_logs entries that have "Assisted Renewal Submitted (App: ...)"
 * to just "Assisted Renewal"
 * 
 * Access: https://yourdomain.com/Admin_BOFETODA/cleanup-assisted-renewal-logs.php?key=cleanup456
 */

// Security key
$CLEANUP_KEY = 'cleanup456';

// Check security key
if (!isset($_GET['key']) || $_GET['key'] !== $CLEANUP_KEY) {
    http_response_code(403);
    die('Access denied. Invalid security key.');
}

require_once 'db.php';

$pdo = get_pdo();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Assisted Renewal Logs</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
            color: #333;
            margin-bottom: 20px;
        }
        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
        }
        .success {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .old-value {
            color: #d32f2f;
            text-decoration: line-through;
        }
        .new-value {
            color: #388e3c;
            font-weight: 600;
        }
        button {
            background: #2196f3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #1976d2;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Cleanup Assisted Renewal Logs</h1>
        
        <?php
        // Check if cleanup should be executed
        if (isset($_POST['execute'])) {
            echo '<div class="info">Starting cleanup process...</div>';
            
            try {
                // Update all entries that match the pattern
                $sql = "UPDATE activity_logs 
                        SET action = 'Assisted Renewal'
                        WHERE action LIKE 'Assisted Renewal Submitted (App:%'";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $updated = $stmt->rowCount();
                
                echo '<div class="success">';
                echo '<strong>✓ Cleanup completed successfully!</strong><br>';
                echo "Updated <strong>{$updated}</strong> record(s).";
                echo '</div>';
                
                if ($updated > 0) {
                    echo '<p>All "Assisted Renewal Submitted (App: ...)" entries have been changed to "Assisted Renewal".</p>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="warning">';
                echo '<strong>⚠ Error during cleanup:</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            
        } else {
            // Show preview of what will be changed
            try {
                $sql = "SELECT id, action, logged_at 
                        FROM activity_logs 
                        WHERE action LIKE 'Assisted Renewal Submitted (App:%'
                        ORDER BY logged_at DESC 
                        LIMIT 20";
                
                $stmt = $pdo->query($sql);
                $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $totalCount = count($entries);
                
                // Get total count
                $countStmt = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action LIKE 'Assisted Renewal Submitted (App:%'");
                $totalCount = $countStmt->fetchColumn();
                
                if ($totalCount > 0) {
                    echo '<div class="info">';
                    echo "<strong>Found {$totalCount} record(s)</strong> that will be updated.";
                    echo '</div>';
                    
                    echo '<h3>Preview (showing first 20 records):</h3>';
                    echo '<table>';
                    echo '<thead><tr><th>ID</th><th>Current Action</th><th>Will Become</th><th>Date</th></tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($entries as $entry) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($entry['id']) . '</td>';
                        echo '<td class="old-value">' . htmlspecialchars($entry['action']) . '</td>';
                        echo '<td class="new-value">Assisted Renewal</td>';
                        echo '<td>' . htmlspecialchars($entry['logged_at']) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                    
                    echo '<div class="warning">';
                    echo '<strong>⚠ Warning:</strong> This action cannot be undone. Make sure you have a database backup before proceeding.';
                    echo '</div>';
                    
                    echo '<form method="POST">';
                    echo '<button type="submit" name="execute" value="1">Execute Cleanup</button>';
                    echo '</form>';
                    
                } else {
                    echo '<div class="success">';
                    echo '<strong>✓ No records need cleanup!</strong><br>';
                    echo 'All assisted renewal log entries are already in the correct format.';
                    echo '</div>';
                }
                
            } catch (PDOException $e) {
                echo '<div class="warning">';
                echo '<strong>⚠ Error checking records:</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        }
        ?>
        
    </div>
</body>
</html>
