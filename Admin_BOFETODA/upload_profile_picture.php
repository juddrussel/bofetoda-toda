<?php
require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
if (!$officer) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_profile_picture') {
    try {
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error occurred.');
        }

        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.');
        }

        // Max 5MB
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size must be less than 5MB.');
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/uploads/profiles';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $officer['id'] . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to move uploaded file.');
        }

        // Update database
        $pdo = get_pdo();
        $relativeUrl = BASE_URL . '/uploads/profiles/' . $filename;
        
        // Delete old profile picture if exists
        $oldPicture = $officer['profile_picture'];
        if ($oldPicture && file_exists(__DIR__ . str_replace(BASE_URL, '', $oldPicture))) {
            unlink(__DIR__ . str_replace(BASE_URL, '', $oldPicture));
        }

        $stmt = $pdo->prepare('UPDATE officers SET profile_picture = :picture WHERE officer_id = :id');
        $stmt->execute([
            'picture' => $relativeUrl,
            'id' => $officer['id']
        ]);

        $_SESSION['upload_success'] = 'Profile picture updated successfully.';
        
    } catch (Exception $e) {
        error_log('Profile picture upload error: ' . $e->getMessage());
        $_SESSION['upload_error'] = $e->getMessage();
    }
}

// Redirect back to the page that sent the request
$returnTo = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/dashboard.php';
header('Location: ' . $returnTo);
exit;
