<?php
/**
 * Push Notification Unsubscribe Endpoint
 * Removes push subscription from database
 */

// Determine if this is a driver or officer based on session
session_start();

$isDriver = isset($_SESSION['driver_id']);
$isOfficer = isset($_SESSION['officer_id']);

if (!$isDriver && !$isOfficer) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get user info
if ($isDriver) {
    require_once __DIR__ . '/Driver_BOFETODA/driver-database.php';
    $userId = $_SESSION['driver_id'];
    $userType = 'driver';
} else {
    require_once __DIR__ . '/Admin_BOFETODA/config.php';
    $userId = $_SESSION['officer_id'];
    $userType = 'officer';
}

// Get PDO connection
$pdo = get_pdo();

// Read JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['endpoint'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$endpoint = $data['endpoint'];

try {
    // Delete subscription
    $stmt = $pdo->prepare("
        DELETE FROM push_subscriptions 
        WHERE user_id = :user_id 
        AND user_type = :user_type 
        AND endpoint = :endpoint
    ");
    
    $stmt->execute([
        'user_id' => $userId,
        'user_type' => $userType,
        'endpoint' => $endpoint
    ]);
    
    $rowsDeleted = $stmt->rowCount();
    
    error_log("Push subscription removed for $userType: $userId (rows: $rowsDeleted)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Subscription removed successfully',
        'rows_deleted' => $rowsDeleted
    ]);
    
} catch (PDOException $e) {
    error_log('Push unsubscribe error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
