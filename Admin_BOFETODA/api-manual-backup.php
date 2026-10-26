<?php
/**
 * Manual Database Backup API
 * Generates mysqldump and downloads to browser
 * Access: President only
 */

require_once __DIR__ . '/config.php';
require_role('president');

// Generate filename with timestamp
$timestamp = date('Y-m-d_His');
$filename = "bofetoda_backup_{$timestamp}.sql.gz";

// Get database credentials
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'bofetoda';

// Build mysqldump command
$command = sprintf(
    'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --lock-tables=false %s | gzip',
    escapeshellarg($host),
    escapeshellarg($port),
    escapeshellarg($user),
    escapeshellarg($pass),
    escapeshellarg($dbname)
);

// Set headers for download
header('Content-Type: application/gzip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Execute mysqldump and stream to browser
$descriptorspec = [
    0 => ['pipe', 'r'],  // stdin
    1 => ['pipe', 'w'],  // stdout
    2 => ['pipe', 'w']   // stderr
];

$process = proc_open($command, $descriptorspec, $pipes);

if (is_resource($process)) {
    // Close stdin
    fclose($pipes[0]);
    
    // Stream stdout to browser
    while (!feof($pipes[1])) {
        echo fread($pipes[1], 8192);
        flush();
    }
    fclose($pipes[1]);
    
    // Capture stderr for error logging
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    
    // Get return value
    $returnValue = proc_close($process);
    
    // Log errors if any
    if ($returnValue !== 0) {
        error_log("Manual backup failed with return code $returnValue: $errors");
    }
} else {
    // Failed to execute command
    header('HTTP/1.1 500 Internal Server Error');
    echo "Failed to create database backup. Please contact your system administrator.";
    error_log('Failed to execute mysqldump command');
}
