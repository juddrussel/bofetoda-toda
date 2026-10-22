<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
if (!in_array($officer['role'] ?? '', ['secretary', 'president'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/admin-documents.php';

$appNo   = $_GET['app'] ?? '';
$docType = $_GET['doc'] ?? '';

$app = $appNo !== '' ? get_application_with_docs($appNo) : null;
$doc = $app['docs'][$docType] ?? null;

if (!$doc || empty($doc['file_path'])) {
    http_response_code(404);
    exit('No document on file.');
}

// Check if file is in R2 storage
$filePath = $doc['file_path'];

// If it's an R2 key (documents/, avatars/, announcements/), redirect to R2 URL
if (preg_match('#^(documents|avatars|announcements)/#', $filePath)) {
    $publicUrl = getenv('R2_PUBLIC_URL');
    if ($publicUrl) {
        $r2Url = rtrim($publicUrl, '/') . '/' . $filePath;
        header('Location: ' . $r2Url);
        exit;
    }
}

// Otherwise, serve from local storage
$path = resolve_document_path($filePath);
if (!$path || !is_file($path)) {
    http_response_code(404);
    exit('File is missing on disk. Check DRIVER_UPLOADS_DIR in admin-documents.php.');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
switch ($ext) {
    case 'jpg':
    case 'jpeg':
        $mime = 'image/jpeg';
        break;
    case 'png':
        $mime = 'image/png';
        break;
    case 'pdf':
        $mime = 'application/pdf';
        break;
    default:
        $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('Cache-Control: private, max-age=0, no-cache');
readfile($path);
exit;