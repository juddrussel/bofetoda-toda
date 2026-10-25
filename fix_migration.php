<?php
/**
 * Fix Migration - Add Missing Columns
 * Run this to properly add the assisted renewal columns
 * SECURITY: Delete this file after running!
 */

require_once 'Admin_BOFETODA/db.php';

$pdo = get_pdo();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #252526;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        
        h1 {
            color: #4ec9b0;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .success {
            color: #4ec9b0;
            padding: 8px 0;
        }
        
        .error {
            color: #f48771;
            padding: 8px 0;
        }
        
        .warning {
            background: #5a4a1e;
            color: #dcdcaa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #dcdcaa;
        }
        
        .btn-group {
            margin: 30px 0;
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .output {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .delete-warning {
            background: #5a1e1e;
            color: #f48771;
            padding: 15px;
            border-radius: 4px;
            margin-top: 30px;
            border-left: 4px solid #f48771;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Migration</h1>

<?php
$action = $_GET['action'] ?? 'info';

if ($action === 'info') {
    ?>
    <div class="warning">
        ⚠️ This will add the missing columns to your database.<br>
        Click "Run Fix" to proceed.
    </div>
    
    <div class="btn-group">
        <a href="?action=fix" class="btn">🚀 Run Fix</a>
        <a href="check_db_status.php" class="btn" style="background: #6c757d;">🔍 Check Status</a>
    </div>
    <?php
} elseif ($action === 'fix') {
    echo '<div class="output">';
    
    $steps = [
        // Applications table columns
        [
            'name' => 'Add uploaded_by_officer_id to applications',
            'sql' => "ALTER TABLE applications ADD COLUMN uploaded_by_officer_id VARCHAR(20) NULL COMMENT 'Officer who submitted on behalf of driver'"
        ],
        [
            'name' => 'Add consent_given to applications',
            'sql' => "ALTER TABLE applications ADD COLUMN consent_given TINYINT(1) DEFAULT 0 COMMENT 'Driver consent for staff-assisted submission'"
        ],
        [
            'name' => 'Add is_staff_assisted to applications',
            'sql' => "ALTER TABLE applications ADD COLUMN is_staff_assisted TINYINT(1) DEFAULT 0 COMMENT 'Flag for staff-assisted renewals'"
        ],
        
        // Application_documents table column
        [
            'name' => 'Add uploaded_by_officer_id to application_documents',
            'sql' => "ALTER TABLE application_documents ADD COLUMN uploaded_by_officer_id VARCHAR(20) NULL COMMENT 'Officer who uploaded this specific document'"
        ],
        
        // Foreign keys
        [
            'name' => 'Add foreign key for applications.uploaded_by_officer_id',
            'sql' => "ALTER TABLE applications ADD CONSTRAINT fk_app_uploaded_by_officer FOREIGN KEY (uploaded_by_officer_id) REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL"
        ],
        [
            'name' => 'Add foreign key for application_documents.uploaded_by_officer_id',
            'sql' => "ALTER TABLE application_documents ADD CONSTRAINT fk_doc_uploaded_by_officer FOREIGN KEY (uploaded_by_officer_id) REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL"
        ],
        
        // Indexes
        [
            'name' => 'Add index on applications.uploaded_by_officer_id',
            'sql' => "CREATE INDEX idx_applications_uploaded_by ON applications(uploaded_by_officer_id)"
        ],
        [
            'name' => 'Add index on applications.is_staff_assisted',
            'sql' => "CREATE INDEX idx_applications_staff_assisted ON applications(is_staff_assisted)"
        ],
        [
            'name' => 'Add index on application_documents.uploaded_by_officer_id',
            'sql' => "CREATE INDEX idx_documents_uploaded_by ON application_documents(uploaded_by_officer_id)"
        ],
    ];
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($steps as $step) {
        try {
            $pdo->exec($step['sql']);
            echo "<div class='success'>✅ {$step['name']}</div>";
            $successCount++;
        } catch (PDOException $e) {
            // Check if error is because column/constraint already exists
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'Duplicate key') !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "<div class='warning' style='color: #dcdcaa; background: transparent; border: none; padding: 8px 0;'>⚠️ {$step['name']} - Already exists (skipped)</div>";
            } else {
                echo "<div class='error'>❌ {$step['name']}<br>Error: {$e->getMessage()}</div>";
                $errorCount++;
            }
        }
    }
    
    echo '<hr style="border: 1px solid #3e3e42; margin: 20px 0;">';
    echo "<div class='success' style='font-size: 18px; font-weight: bold;'>✅ Completed: $successCount steps successful</div>";
    
    if ($errorCount > 0) {
        echo "<div class='error' style='font-size: 18px; font-weight: bold;'>❌ Errors: $errorCount steps failed</div>";
    }
    
    echo '</div>';
    
    echo '<div class="btn-group">';
    echo '<a href="../check_db_status.php" class="btn">🔍 Check Status Now</a>';
    echo '</div>';
}
?>

        <div class="delete-warning">
            🔥 SECURITY: Delete both fix_migration.php and check_db_status.php after completing!
        </div>
    </div>
</body>
</html>
