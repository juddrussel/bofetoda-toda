<?php
/**
 * Download Application Document
 * Allows drivers to download their submitted application documents
 */

require_once __DIR__ . '/driver-database.php';

// Require driver login
$driver = require_driver();

// Get parameters
$app_no = $_GET['app_no'] ?? '';
$doc_type = $_GET['doc_type'] ?? '';

if (!$app_no || !$doc_type) {
    http_response_code(400);
    die('Missing required parameters');
}

// Verify this application belongs to the logged-in driver
$pdo = get_pdo();
$appCheck = $pdo->prepare('SELECT driver_id FROM applications WHERE app_no = :app_no');
$appCheck->execute(['app_no' => $app_no]);
$app = $appCheck->fetch();

if (!$app || $app['driver_id'] !== $driver['driver_id']) {
    http_response_code(403);
    die('Access denied');
}

// Get the document
$docStmt = $pdo->prepare('SELECT file_path FROM application_documents WHERE app_no = :app_no AND doc_type = :doc_type');
$docStmt->execute([
    'app_no' => $app_no,
    'doc_type' => $doc_type
]);
$document = $docStmt->fetch();

if (!$document || !$document['file_path']) {
    http_response_code(404);
    die('Document not found');
}

// Build full file path
$filePath = $document['file_path'];

// Handle both absolute and relative paths
if (!file_exists($filePath)) {
    // Try relative to uploads directory
    $uploadsDir = __DIR__ . '/uploads/documents/';
    $filePath = $uploadsDir . ltrim($filePath, '/');
}

if (!file_exists($filePath)) {
    error_log("Application document not found: {$document['file_path']}");
    http_response_code(404);
    die('File not found on server');
}

// Get file info
$fileInfo = pathinfo($filePath);
$extension = strtolower($fileInfo['extension'] ?? '');

// Determine MIME type
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
$isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);

// Determine filename for download
$docTypeNames = [
    'license' => 'drivers_license',
    'or_cr' => 'or_cr',
    'receipt' => 'payment_receipt',
    'photo' => '1x1_picture'
];
$downloadName = ($docTypeNames[$doc_type] ?? $doc_type) . '.' . $extension;

// Set headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));

// For images, display inline; for others, force download
if ($isImage) {
    header('Content-Disposition: inline; filename="' . $downloadName . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
}

header('Cache-Control: private, max-age=3600');
header('Pragma: private');

// Output file
readfile($filePath);
exit;
