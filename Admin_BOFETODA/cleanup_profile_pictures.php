<?php
/**
 * Cleanup invalid profile picture entries
 * Run this once to remove broken profile_picture references from the database
 */

require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Profile Pictures</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
        h1 { color: #2d3748; font-size: 24px; margin-bottom: 20px; }
        .result { padding: 15px; border-radius: 8px; margin: 15px 0; }
        .result.success { background: #c6f6d5; color: #22543d; }
        .result.info { background: #bee3f8; color: #2c5282; }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover { background: #5a67d8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Cleanup Profile Pictures</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $pdo = get_pdo();
                
                // Get all officers with profile pictures
                $stmt = $pdo->query("SELECT officer_id, name, profile_picture FROM officers WHERE profile_picture IS NOT NULL AND profile_picture != ''");
                $officers = $stmt->fetchAll();
                
                $cleaned = 0;
                $valid = 0;
                
                foreach ($officers as $officer) {
                    $picturePath = $officer['profile_picture'];
                    $filePath = __DIR__ . str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $picturePath);
                    
                    if (!file_exists($filePath)) {
                        // File doesn't exist, clear the database entry
                        $updateStmt = $pdo->prepare("UPDATE officers SET profile_picture = NULL WHERE officer_id = ?");
                        $updateStmt->execute([$officer['officer_id']]);
                        $cleaned++;
                        echo '<div class="result info">Cleared broken link for ' . htmlspecialchars($officer['name']) . '</div>';
                    } else {
                        $valid++;
                    }
                }
                
                echo '<div class="result success">';
                echo '<strong>✅ Cleanup Complete!</strong><br>';
                echo "Valid pictures: $valid<br>";
                echo "Cleaned up: $cleaned";
                echo '</div>';
                
                echo '<p style="margin-top:20px;">You can now delete this file for security.</p>';
                
            } catch (Exception $e) {
                echo '<div class="result" style="background:#fed7d7; color:#742a2a;">';
                echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        } else {
            echo '<p style="color:#4a5568; margin-bottom:20px;">This will remove any profile_picture database entries that point to non-existent files.</p>';
            echo '<form method="POST">';
            echo '<button type="submit" class="btn">Run Cleanup</button>';
            echo '</form>';
        }
        ?>
    </div>
</body>
</html>
