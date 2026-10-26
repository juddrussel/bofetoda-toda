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
    
    // Get database credentials
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $dbname = getenv('DB_NAME') ?: 'bofetoda';
    
    // Build mysqldump command (save to temp file)
    $command = sprintf(
        'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --lock-tables=false %s | gzip > %s',
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($user),
        escapeshellarg($pass),
        escapeshellarg($dbname),
        escapeshellarg($tempFile)
    );
    
    // Execute mysqldump
    $output = [];
    $returnCode = 0;
    exec($command . ' 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        throw new Exception('mysqldump failed: ' . implode("\n", $output));
    }
    
    // Check if file was created and has content
    if (!file_exists($tempFile) || filesize($tempFile) === 0) {
        throw new Exception('Backup file is empty or was not created');
    }
    
    $fileSize = filesize($tempFile);
    
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
    error_log("Automated backup completed: {$filename} ({$fileSize} bytes), cleaned up {$deletedCount} old backups");
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Backup completed successfully',
        'filename' => $filename,
        'size' => format_bytes($fileSize),
        'size_bytes' => $fileSize,
        'r2_key' => $r2Key,
        'timestamp' => date('Y-m-d H:i:s'),
        'old_backups_deleted' => $deletedCount
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
