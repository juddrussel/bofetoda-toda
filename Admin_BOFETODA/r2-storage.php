<?php

/**
 * Cloudflare R2 Storage Integration
 * Handles all file uploads to R2 bucket using AWS S3 SDK
 * 
 * Folder Structure:
 * - announcements/           General announcement images
 * - documents/{driver_id}/   Driver renewal documents (license, OR/CR, receipts, 1x1 photos)
 * - avatars/                 Driver profile avatars
 * - admin/                   Admin-uploaded files
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Get configured R2 client
 */
function get_r2_client() {
    static $client = null;
    
    if ($client !== null) {
        return $client;
    }
    
    $accessKeyId = getenv('R2_ACCESS_KEY_ID');
    $secretAccessKey = getenv('R2_SECRET_ACCESS_KEY');
    $endpoint = getenv('R2_ENDPOINT');
    
    if (!$accessKeyId || !$secretAccessKey || !$endpoint) {
        error_log('R2 credentials not configured. Falling back to local storage.');
        return null;
    }
    
    try {
        $client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => 'https://' . $endpoint,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
            'use_path_style_endpoint' => false,
        ]);
        
        return $client;
    } catch (Exception $e) {
        error_log('Failed to initialize R2 client: ' . $e->getMessage());
        return null;
    }
}

/**
 * Check if R2 storage is configured and available
 */
function is_r2_enabled() {
    return get_r2_client() !== null;
}

/**
 * Upload a file to R2 storage
 * 
 * @param array $file File data from $_FILES
 * @param string $objectKey The key (path) to store the file in R2
 * @return string|false Public URL of uploaded file or false on failure
 */
function r2_upload_file($file, $objectKey) {
    $client = get_r2_client();
    
    if (!$client) {
        return false;
    }
    
    $bucketName = getenv('R2_BUCKET_NAME') ?: 'bofetoda-announcements';
    $publicUrl = getenv('R2_PUBLIC_URL');
    
    try {
        // Upload file
        $result = $client->putObject([
            'Bucket' => $bucketName,
            'Key' => $objectKey,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $file['type'],
            'ACL' => 'public-read', // Make publicly accessible
        ]);
        
        // Return public URL
        if ($publicUrl) {
            return rtrim($publicUrl, '/') . '/' . $objectKey;
        } else {
            // Fallback to ObjectURL from result
            return $result['ObjectURL'] ?? false;
        }
    } catch (AwsException $e) {
        error_log('R2 upload failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete a file from R2 storage
 * 
 * @param string $objectKey The key (path) of the file in R2
 * @return bool Success status
 */
function r2_delete_file($objectKey) {
    $client = get_r2_client();
    
    if (!$client) {
        return false;
    }
    
    $bucketName = getenv('R2_BUCKET_NAME') ?: 'bofetoda-announcements';
    
    try {
        $client->deleteObject([
            'Bucket' => $bucketName,
            'Key' => $objectKey,
        ]);
        
        return true;
    } catch (AwsException $e) {
        error_log('R2 delete failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Upload announcement image to R2 or local storage (fallback)
 * 
 * @param int $announcementId Announcement ID
 * @param array $file File data from $_FILES
 * @return array|false ['url' => string, 'path' => string] or false on failure
 */
function upload_announcement_image_r2($announcementId, $file) {
    // Validate image first
    if (!validate_announcement_image($file)) {
        return false;
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'announcements/announcement_' . $announcementId . '_' . time() . '_' . uniqid() . '.' . $extension;
    
    // Try R2 first
    if (is_r2_enabled()) {
        $url = r2_upload_file($file, $filename);
        
        if ($url) {
            return [
                'url' => $url,
                'path' => $filename, // Store key for deletion
                'storage' => 'r2'
            ];
        }
        
        error_log('R2 upload failed, falling back to local storage');
    }
    
    // Fallback to local storage
    $uploadDir = __DIR__ . '/../uploads/announcements/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $localFilename = 'announcement_' . $announcementId . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $localFilename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'url' => 'uploads/announcements/' . $localFilename,
            'path' => 'uploads/announcements/' . $localFilename,
            'storage' => 'local'
        ];
    }
    
    return false;
}

/**
 * Delete announcement image from R2 or local storage
 * 
 * @param string $imagePath Path or key of the image
 * @return bool Success status
 */
function delete_announcement_image_r2($imagePath) {
    // Check if it's an R2 URL
    $publicUrl = getenv('R2_PUBLIC_URL');
    
    if ($publicUrl && strpos($imagePath, $publicUrl) === 0) {
        // Extract key from URL
        $objectKey = str_replace(rtrim($publicUrl, '/') . '/', '', $imagePath);
        return r2_delete_file($objectKey);
    }
    
    // Check if it's an R2 key (starts with announcements/)
    if (strpos($imagePath, 'announcements/') === 0) {
        if (is_r2_enabled()) {
            return r2_delete_file($imagePath);
        }
    }
    
    // Fallback to local deletion
    $filepath = __DIR__ . '/../' . $imagePath;
    if (file_exists($filepath)) {
        return @unlink($filepath);
    }
    
    return false;
}


/**
 * Upload driver renewal document to R2
 * 
 * @param string $driverId Driver ID (e.g., DRV-BG0001)
 * @param string $documentType Type of document (drivers_license, or_cr, payment_receipt, picture_1x1)
 * @param array $file File data from $_FILES
 * @return array|false ['url' => string, 'path' => string, 'storage' => 'r2'|'local'] or false on failure
 */
function upload_driver_document_r2($driverId, $documentType, $file) {
    // Validate file
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
    $maxSize = 4 * 1024 * 1024; // 4MB
    
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        error_log("File too large: {$file['size']} bytes");
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExt, true)) {
        error_log("Invalid file type: $extension");
        return false;
    }
    
    // Generate R2 key with driver folder structure
    $filename = $documentType . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $r2Key = 'documents/' . $driverId . '/' . $filename;
    
    // Try R2 first
    if (is_r2_enabled()) {
        $url = r2_upload_file($file, $r2Key);
        
        if ($url) {
            return [
                'url' => $url,
                'path' => $r2Key, // Store R2 key for database
                'storage' => 'r2'
            ];
        }
        
        error_log('R2 upload failed for driver document, falling back to local storage');
    }
    
    // Fallback to local storage
    $uploadDir = __DIR__ . '/../Driver_BOFETODA/uploads/documents/' . $driverId . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $localFilename = $documentType . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $filepath = $uploadDir . $localFilename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'url' => 'uploads/documents/' . $driverId . '/' . $localFilename,
            'path' => 'uploads/documents/' . $driverId . '/' . $localFilename,
            'storage' => 'local'
        ];
    }
    
    return false;
}

/**
 * Upload driver avatar to R2
 * 
 * @param string $driverId Driver ID
 * @param array $file File data from $_FILES
 * @return array|false ['url' => string, 'path' => string, 'storage' => 'r2'|'local'] or false on failure
 */
function upload_driver_avatar_r2($driverId, $file) {
    // Validate file
    $allowedExt = ['jpg', 'jpeg', 'png'];
    $maxSize = 4 * 1024 * 1024; // 4MB
    
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExt, true)) {
        return false;
    }
    
    // Delete old avatars first (all extensions)
    foreach ($allowedExt as $ext) {
        $oldKey = 'avatars/' . $driverId . '.' . $ext;
        delete_driver_file_r2($oldKey);
    }
    
    // Generate R2 key
    $r2Key = 'avatars/' . $driverId . '.' . $extension;
    
    // Try R2 first
    if (is_r2_enabled()) {
        $url = r2_upload_file($file, $r2Key);
        
        if ($url) {
            return [
                'url' => $url,
                'path' => $r2Key,
                'storage' => 'r2'
            ];
        }
        
        error_log('R2 upload failed for avatar, falling back to local storage');
    }
    
    // Fallback to local storage
    $uploadDir = __DIR__ . '/../Driver_BOFETODA/uploads/avatars/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Delete old local avatars
    foreach ($allowedExt as $ext) {
        $oldFile = $uploadDir . $driverId . '.' . $ext;
        if (file_exists($oldFile)) {
            @unlink($oldFile);
        }
    }
    
    $localPath = $uploadDir . $driverId . '.' . $extension;
    
    if (move_uploaded_file($file['tmp_name'], $localPath)) {
        return [
            'url' => 'uploads/avatars/' . $driverId . '.' . $extension,
            'path' => 'uploads/avatars/' . $driverId . '.' . $extension,
            'storage' => 'local'
        ];
    }
    
    return false;
}

/**
 * Delete driver file from R2 or local storage
 * 
 * @param string $filePath Path or key of the file
 * @return bool Success status
 */
function delete_driver_file_r2($filePath) {
    // Check if it's an R2 URL
    $publicUrl = getenv('R2_PUBLIC_URL');
    
    if ($publicUrl && strpos($filePath, $publicUrl) === 0) {
        // Extract key from URL
        $objectKey = str_replace(rtrim($publicUrl, '/') . '/', '', $filePath);
        return r2_delete_file($objectKey);
    }
    
    // Check if it's an R2 key (starts with documents/, avatars/, announcements/)
    if (preg_match('#^(documents|avatars|announcements)/#', $filePath)) {
        if (is_r2_enabled()) {
            return r2_delete_file($filePath);
        }
    }
    
    // Fallback to local deletion
    // Handle both absolute and relative paths
    if (strpos($filePath, 'uploads/') === 0) {
        $filepath = __DIR__ . '/../Driver_BOFETODA/' . $filePath;
    } elseif (strpos($filePath, 'Driver_BOFETODA/uploads/') !== false) {
        $filepath = __DIR__ . '/../' . substr($filePath, strpos($filePath, 'Driver_BOFETODA/'));
    } else {
        $filepath = __DIR__ . '/../' . $filePath;
    }
    
    if (file_exists($filepath)) {
        return @unlink($filepath);
    }
    
    return false;
}

/**
 * Get full URL for any file (documents, avatars, announcements)
 * Handles both R2 URLs and local paths
 * 
 * @param string $filePath File path from database
 * @return string Full URL to the file
 */
function get_file_url($filePath) {
    // If it's already a full URL (R2), return as-is
    if (strpos($filePath, 'https://') === 0 || strpos($filePath, 'http://') === 0) {
        return $filePath;
    }
    
    // Check if it's an R2 key (starts with documents/, avatars/, announcements/)
    if (preg_match('#^(documents|avatars|announcements)/#', $filePath)) {
        $publicUrl = getenv('R2_PUBLIC_URL');
        if ($publicUrl) {
            return rtrim($publicUrl, '/') . '/' . $filePath;
        }
    }
    
    // Fallback to local path
    // Remove leading slash if present
    $filePath = ltrim($filePath, '/');
    
    // For driver portal files, navigate up one directory
    if (strpos($filePath, 'uploads/') === 0) {
        return '../' . $filePath;
    }
    
    return $filePath;
}

/**
 * Batch upload multiple driver documents
 * 
 * @param string $driverId Driver ID
 * @param array $files Array of files from $_FILES (e.g., ['drivers_license' => $file, 'or_cr' => $file])
 * @return array ['success' => bool, 'paths' => array, 'errors' => array]
 */
function batch_upload_driver_documents($driverId, $files) {
    $result = [
        'success' => true,
        'paths' => [],
        'errors' => []
    ];
    
    foreach ($files as $documentType => $file) {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $result['success'] = false;
            $result['errors'][$documentType] = 'Upload error or file missing';
            continue;
        }
        
        $uploadResult = upload_driver_document_r2($driverId, $documentType, $file);
        
        if ($uploadResult) {
            $result['paths'][$documentType] = $uploadResult['path'];
        } else {
            $result['success'] = false;
            $result['errors'][$documentType] = 'Upload failed';
        }
    }
    
    return $result;
}
