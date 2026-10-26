<?php
/**
 * PHP-based Database Backup (No mysqldump required)
 * Generates SQL dump using pure PHP
 * Access: President only
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_role('president');

set_time_limit(300); // 5 minutes max
ini_set('memory_limit', '256M');

// Generate filename with timestamp
$timestamp = date('Y-m-d_His');
$filename = "bofetoda_backup_{$timestamp}.sql";

try {
    // Get database connection
    $pdo = get_pdo();
    
    // Start output buffering
    ob_start();
    
    // SQL Header
    echo "-- BOFETODA Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Host: " . (getenv('DB_HOST') ?: '127.0.0.1') . "\n";
    echo "-- Database: " . (getenv('DB_NAME') ?: 'bofetoda') . "\n\n";
    echo "SET NAMES utf8mb4;\n";
    echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "-- ----------------------------\n";
        echo "-- Table structure for $table\n";
        echo "-- ----------------------------\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";
        
        // Get CREATE TABLE statement
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        echo $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        echo "-- ----------------------------\n";
        echo "-- Records of $table\n";
        echo "-- ----------------------------\n";
        
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            // Get column names
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            // Insert in batches of 100 rows
            $batchSize = 100;
            $batches = array_chunk($rows, $batchSize);
            
            foreach ($batches as $batch) {
                echo "INSERT INTO `$table` ($columnList) VALUES\n";
                
                $values = [];
                foreach ($batch as $row) {
                    $escapedValues = array_map(function($value) use ($pdo) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return $pdo->quote($value);
                    }, array_values($row));
                    $values[] = '(' . implode(', ', $escapedValues) . ')';
                }
                
                echo implode(",\n", $values) . ";\n\n";
            }
        }
    }
    
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Get the SQL content
    $sqlContent = ob_get_clean();
    
    // Compress with gzip
    $compressed = gzencode($sqlContent, 9);
    
    // Send headers
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $filename . '.gz"');
    header('Content-Length: ' . strlen($compressed));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $compressed;
    
    // Log success
    error_log("PHP-based backup created successfully: " . strlen($compressed) . " bytes (compressed from " . strlen($sqlContent) . " bytes)");
    
} catch (Exception $e) {
    // Error occurred
    header('Content-Type: text/plain');
    echo "Backup failed!\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    error_log("PHP-based backup failed: " . $e->getMessage());
}
