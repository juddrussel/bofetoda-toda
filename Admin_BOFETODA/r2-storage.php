<?php

/**
 * Cloudflare R2 Storage Integration
 * Handles image uploads to R2 bucket using AWS S3 SDK
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
