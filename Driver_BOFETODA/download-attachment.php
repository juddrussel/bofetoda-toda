<?php
require 'driver-database.php';

$driver = require_driver();
$driver_id = $driver['id'];

// Get attachment ID
$attachment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($attachment_id <= 0) {
    http_response_code(400);
    die('Invalid attachment ID');
}

// Get attachment details and verify access
$pdo = get_pdo();
try {
    $stmt = $pdo->prepare("
        SELECT ma.*, dm.driver_id 
        FROM message_attachments ma
        INNER JOIN driver_messages dm ON ma.message_id = dm.id
        WHERE ma.id = ? AND dm.driver_id = ?
    ");
    $stmt->execute([$attachment_id, $driver_id]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        http_response_code(403);
        die('Attachment not found or access denied');
    }
    
    // Determine if file is in R2 or local storage
    $is_r2 = !empty($attachment['r2_key']);
    
    if ($is_r2) {
        // Redirect to R2 URL
        require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';
        $download_url = get_message_attachment_url($attachment['r2_key']);
        
        if ($download_url) {
            header('Location: ' . $download_url);
            exit;
        } else {
            http_response_code(500);
            die('Failed to generate download URL');
        }
    } else {
        // Serve from local storage
        $file_path = __DIR__ . '/../' . $attachment['file_path'];
        
        if (!file_exists($file_path)) {
            http_response_code(404);
            die('File not found');
        }
        
        // Check if it's an image for inline display
        $is_image = preg_match('/^image\//i', $attachment['file_type']);
        
        // Set headers
        header('Content-Type: ' . $attachment['file_type']);
        
        // For images, display inline; for other files, force download
        if ($is_image) {
            header('Content-Disposition: inline; filename="' . $attachment['file_name'] . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
        }
        
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        
        // Output file
        readfile($file_path);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Attachment download error: " . $e->getMessage());
    http_response_code(500);
    die('Database error');
}
