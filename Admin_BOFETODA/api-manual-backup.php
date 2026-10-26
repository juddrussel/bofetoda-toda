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
    
    // Capture stderr for error checking
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    
    // Read stdout into buffer first to check if we got data
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    
    // Get return value
    $returnValue = proc_close($process);
    
    // Check for errors or empty output
    if ($returnValue !== 0 || empty($output) || strlen($output) < 100) {
        // Something went wrong - show error instead of downloading empty file
        header('Content-Type: text/plain');
        echo "Backup failed!\n\n";
        echo "Return code: $returnValue\n";
        echo "Output size: " . strlen($output) . " bytes\n\n";
        echo "Errors:\n$errors\n\n";
        echo "Command attempted:\n$command\n\n";
        echo "Database connection details:\n";
        echo "Host: $host:$port\n";
        echo "Database: $dbname\n";
        echo "User: $user\n";
        
        error_log("Manual backup failed - return code: $returnValue, output size: " . strlen($output) . ", errors: $errors");
        exit;
    }
    
    // Success - send the backup file
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($output));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $output;
    
} else {
    // Failed to execute command
    header('Content-Type: text/plain');
    echo "Failed to create database backup - could not execute mysqldump command.\n\n";
    echo "This might mean:\n";
    echo "1. mysqldump is not installed\n";
    echo "2. mysqldump is not in the system PATH\n";
    echo "3. PHP proc_open is disabled\n";
    error_log('Failed to execute mysqldump command - proc_open failed');
}
