<?php
/**
 * Database Migration Status Checker
 * SECURITY: Delete this file after checking!
 */

require_once 'Admin_BOFETODA/db.php';

$pdo = get_pdo();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status Check</title>
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
        
        h2 {
            color: #569cd6;
            margin: 30px 0 15px 0;
            font-size: 18px;
            border-bottom: 2px solid #569cd6;
            padding-bottom: 5px;
        }
        
        .success {
            color: #4ec9b0;
        }
        
        .error {
            color: #f48771;
        }
        
        .warning {
            color: #dcdcaa;
        }
        
        .info {
            background: #264f78;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #569cd6;
        }
        
        .result-line {
            padding: 5px 0;
            font-size: 14px;
        }
        
        .column-detail {
            padding-left: 20px;
            color: #9cdcfe;
            font-size: 13px;
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
        <h1>🔍 Database Migration Status</h1>
        
        <div class="info">
            ⚠️ This file checks database structure. Delete it after checking for security.
        </div>

        <?php
        // Check applications table columns
        echo "<h2>📋 APPLICATIONS TABLE</h2>";
        $stmt = $pdo->query("SHOW COLUMNS FROM applications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $foundColumns = [];
        
        $requiredColumns = ['uploaded_by_officer_id', 'consent_given', 'is_staff_assisted'];
        
        foreach ($columns as $col) {
            if (in_array($col['Field'], $requiredColumns)) {
                $foundColumns[] = $col['Field'];
                echo "<div class='result-line success'>✅ {$col['Field']}</div>";
                echo "<div class='column-detail'>Type: {$col['Type']} | Null: {$col['Null']} | Default: {$col['Default']}</div>";
            }
        }

        $missing = array_diff($requiredColumns, $foundColumns);
        foreach ($missing as $m) {
            echo "<div class='result-line error'>❌ Missing: $m</div>";
        }

        // Check application_documents table columns
        echo "<h2>📄 APPLICATION_DOCUMENTS TABLE</h2>";
        $stmt = $pdo->query("SHOW COLUMNS FROM application_documents");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $foundInDocs = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'uploaded_by_officer_id') {
                $foundInDocs = true;
                echo "<div class='result-line success'>✅ {$col['Field']}</div>";
                echo "<div class='column-detail'>Type: {$col['Type']} | Null: {$col['Null']} | Default: {$col['Default']}</div>";
            }
        }

        if (!$foundInDocs) {
            echo "<div class='result-line error'>❌ Missing: uploaded_by_officer_id</div>";
        }

        // Check foreign keys
        echo "<h2>🔗 FOREIGN KEYS</h2>";
        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME IN ('applications', 'application_documents')
            AND CONSTRAINT_NAME LIKE 'fk_%uploaded_by%'
        ");
        $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($fks) > 0) {
            foreach ($fks as $fk) {
                echo "<div class='result-line success'>✅ {$fk['CONSTRAINT_NAME']}</div>";
                echo "<div class='column-detail'>{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</div>";
            }
        } else {
            echo "<div class='result-line warning'>⚠️ No foreign key constraints found (this is OK, indexes are more important)</div>";
        }

        // Check indexes
        echo "<h2>📊 INDEXES</h2>";
        $stmt = $pdo->query("SHOW INDEX FROM applications WHERE Key_name LIKE '%uploaded_by%' OR Key_name LIKE '%staff_assisted%'");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($indexes) > 0) {
            foreach ($indexes as $idx) {
                echo "<div class='result-line success'>✅ {$idx['Key_name']} on {$idx['Column_name']}</div>";
            }
        } else {
            echo "<div class='result-line warning'>⚠️ No indexes found (performance optimization, not critical)</div>";
        }

        $stmt = $pdo->query("SHOW INDEX FROM application_documents WHERE Key_name LIKE '%uploaded_by%'");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($indexes as $idx) {
            echo "<div class='result-line success'>✅ {$idx['Key_name']} on {$idx['Column_name']}</div>";
        }

        // Summary
        echo "<h2>📝 SUMMARY</h2>";
        
        $allColumnsPresent = (count($missing) === 0 && $foundInDocs);
        
        if ($allColumnsPresent) {
            echo "<div class='result-line success'>✅✅✅ All required columns are present!</div>";
            echo "<div class='result-line success'>✅ The assisted renewal feature should work properly.</div>";
            if (count($indexes) === 0) {
                echo "<div class='result-line warning'>⚠️ Indexes are missing but this won't affect functionality, only performance.</div>";
            }
        } else {
            echo "<div class='result-line error'>❌ Some columns are missing. The feature will not work.</div>";
            echo "<div class='result-line warning'>⚠️ You need to run a fix script.</div>";
        }
        ?>

        <div class="delete-warning">
            🔥 SECURITY: Delete this file (check_db_status.php) after checking!
        </div>
    </div>
</body>
</html>
