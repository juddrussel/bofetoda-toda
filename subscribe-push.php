<?php
/**
 * Push Notification Subscription Endpoint
 * Saves push subscription details to database
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

if (!$data || !isset($data['subscription'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

$subscription = $data['subscription'];

// Validate subscription object
if (!isset($subscription['endpoint']) || !isset($subscription['keys']['p256dh']) || !isset($subscription['keys']['auth'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid subscription object']);
    exit;
}

try {
    // Check if subscription already exists
    $stmt = $pdo->prepare("
        SELECT id FROM push_subscriptions 
        WHERE user_id = :user_id 
        AND user_type = :user_type 
        AND endpoint = :endpoint
    ");
    
    $stmt->execute([
        'user_id' => $userId,
        'user_type' => $userType,
        'endpoint' => $subscription['endpoint']
    ]);
    
    if ($stmt->fetch()) {
        // Update existing subscription
        $stmt = $pdo->prepare("
            UPDATE push_subscriptions 
            SET p256dh_key = :p256dh, 
                auth_key = :auth,
                last_used_at = NOW()
            WHERE user_id = :user_id 
            AND user_type = :user_type 
            AND endpoint = :endpoint
        ");
        
        $stmt->execute([
            'p256dh' => $subscription['keys']['p256dh'],
            'auth' => $subscription['keys']['auth'],
            'user_id' => $userId,
            'user_type' => $userType,
            'endpoint' => $subscription['endpoint']
        ]);
        
        $action = 'updated';
    } else {
        // Insert new subscription
        $stmt = $pdo->prepare("
            INSERT INTO push_subscriptions 
            (user_id, user_type, endpoint, p256dh_key, auth_key, last_used_at)
            VALUES (:user_id, :user_type, :endpoint, :p256dh, :auth, NOW())
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'user_type' => $userType,
            'endpoint' => $subscription['endpoint'],
            'p256dh' => $subscription['keys']['p256dh'],
            'auth' => $subscription['keys']['auth']
        ]);
        
        $action = 'created';
    }
    
    error_log("Push subscription $action for $userType: $userId");
    
    echo json_encode([
        'success' => true,
        'message' => 'Subscription saved successfully',
        'action' => $action,
        'user_type' => $userType
    ]);
    
} catch (PDOException $e) {
    error_log('Push subscription error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
