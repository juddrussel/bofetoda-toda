<?php
/**
 * Automated Database Backup API
 * Runs via cron job to create daily backups and upload to R2
 * 
 * Usage: 
 * curl -X POST "https://your-domain.com/Admin_BOFETODA/api-automated-backup.php?key=YOUR_SECRET_KEY"
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/r2-storage.php';

// Set content type to JSON
header('Content-Type: application/json');

// Optional authentication via secret key
$expectedKey = getenv('BACKUP_CRON_KEY');
$providedKey = $_GET['key'] ?? '';

if ($expectedKey && $providedKey !== $expectedKey) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid authentication key'
    ]);
    exit;
}

// Check if R2 is available
if (!is_r2_enabled()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'R2 storage not configured'
    ]);
    error_log('Automated backup failed: R2 storage not configured');
    exit;
}

try {
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_His');
    $filename = "backup_{$timestamp}.sql.gz";
    
    // Create temporary file for backup
    $tempDir = sys_get_temp_dir();
    $tempFile = $tempDir . '/' . $filename;
    
    // Get database connection
    $pdo = get_db();
    
    // Generate SQL backup content using PHP
    $sqlContent = "-- BOFETODA Automated Database Backup\n";
    $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- Host: " . (getenv('DB_HOST') ?: '127.0.0.1') . "\n";
    $sqlContent .= "-- Database: " . (getenv('DB_NAME') ?: 'bofetoda') . "\n\n";
    $sqlContent .= "SET NAMES utf8mb4;\n";
    $sqlContent .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $sqlContent .= "-- ----------------------------\n";
        $sqlContent .= "-- Table structure for $table\n";
        $sqlContent .= "-- ----------------------------\n";
        $sqlContent .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Get CREATE TABLE statement
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $sqlContent .= $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        $sqlContent .= "-- ----------------------------\n";
        $sqlContent .= "-- Records of $table\n";
        $sqlContent .= "-- ----------------------------\n";
        
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            // Get column names
            $columns = array_keys($rows[0]);
            $columnList = '`' . implode('`, `', $columns) . '`';
            
            // Insert in batches of 100 rows
            $batchSize = 100;
            $batches = array_chunk($rows, $batchSize);
            
            foreach ($batches as $batch) {
                $sqlContent .= "INSERT INTO `$table` ($columnList) VALUES\n";
                
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
                
                $sqlContent .= implode(",\n", $values) . ";\n\n";
            }
        }
    }
    
    $sqlContent .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Compress and save to temp file
    $compressed = gzencode($sqlContent, 9);
    file_put_contents($tempFile, $compressed);
    
    $fileSize = strlen($compressed);
    $originalSize = strlen($sqlContent);
    
    // Upload to R2
    $date = date('Y-m-d');
    $r2Key = "backups/{$date}/{$filename}";
    
    $uploadSuccess = upload_backup_to_r2($tempFile, $r2Key);
    
    if (!$uploadSuccess) {
        throw new Exception('Failed to upload backup to R2');
    }
    
    // Delete temp file
    @unlink($tempFile);
    
    // Cleanup old backups (older than 7 days)
    $deletedCount = cleanup_old_backups(7);
    
    // Log success
    error_log("Automated backup completed (PHP method): {$filename} ({$fileSize} bytes compressed from {$originalSize} bytes), cleaned up {$deletedCount} old backups");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Backup completed successfully',
        'filename' => $filename,
        'size' => format_bytes($fileSize),
        'size_bytes' => $fileSize,
        'original_size' => format_bytes($originalSize),
        'r2_key' => $r2Key,
        'timestamp' => date('Y-m-d H:i:s'),
        'old_backups_deleted' => $deletedCount,
        'method' => 'PHP-based'
    ]);
    
} catch (Exception $e) {
    // Cleanup temp file on error
    if (isset($tempFile) && file_exists($tempFile)) {
        @unlink($tempFile);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    error_log('Automated backup failed: ' . $e->getMessage());
}
