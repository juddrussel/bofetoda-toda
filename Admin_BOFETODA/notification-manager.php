<?php
/**
 * Notification Manager
 * BOFETODA TODA Management System
 * 
 * Handles sending web push notifications and creating in-app notifications
 */

require_once __DIR__ . '/config.php';

// Load web-push library
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Send push notification to a specific user
 * 
 * @param string $userId User ID (driver_id or officer_id)
 * @param string $userType 'driver' or 'officer'
 * @param string $title Notification title
 * @param string $body Notification body text
 * @param string|null $url URL to open when notification is clicked
 * @param array $options Additional notification options
 * @return array Result with success count and failures
 */
function send_push_notification($userId, $userType, $title, $body, $url = null, $options = []) {
    if (!is_push_enabled()) {
        error_log('Push notifications not enabled - VAPID keys not configured');
        return ['success' => 0, 'failures' => [], 'error' => 'Push not enabled'];
    }
    
    if (!class_exists('Minishlink\WebPush\WebPush')) {
        error_log('WebPush library not loaded - run composer install');
        return ['success' => 0, 'failures' => [], 'error' => 'WebPush library missing'];
    }
    
    $pdo = get_pdo();
    $vapidConfig = get_vapid_config();
    
    // Get all subscriptions for this user
    $stmt = $pdo->prepare("
        SELECT id, endpoint, p256dh_key, auth_key 
        FROM push_subscriptions 
        WHERE user_id = :user_id 
        AND user_type = :user_type
    ");
    
    $stmt->execute([
        'user_id' => $userId,
        'user_type' => $userType
    ]);
    
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        error_log("No push subscriptions found for $userType: $userId");
        return ['success' => 0, 'failures' => [], 'error' => 'No subscriptions'];
    }
    
    // Prepare notification payload
    $payload = [
        'title' => $title,
        'body' => $body,
        'icon' => $options['icon'] ?? '/Admin_BOFETODA/LOGO_TODA.png',
        'badge' => $options['badge'] ?? '/Admin_BOFETODA/LOGO_BOCAUE.png',
        'url' => $url ?? '/',
        'tag' => $options['tag'] ?? 'bofetoda-notification',
        'requireInteraction' => $options['requireInteraction'] ?? false,
        'vibrate' => $options['vibrate'] ?? [200, 100, 200],
        'data' => $options['data'] ?? []
    ];
    
    // Initialize WebPush
    $auth = [
        'VAPID' => [
            'subject' => $vapidConfig['subject'],
            'publicKey' => $vapidConfig['publicKey'],
            'privateKey' => $vapidConfig['privateKey']
        ]
    ];
    
    $webPush = new WebPush($auth);
    $webPush->setAutomaticPadding(false); // Disable padding for better compatibility
    
    // Send to each subscription
    $successCount = 0;
    $failures = [];
    
    foreach ($subscriptions as $sub) {
        try {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh_key'],
                    'auth' => $sub['auth_key']
                ]
            ]);
            
            $result = $webPush->queueNotification(
                $subscription,
                json_encode($payload)
            );
            
            if ($result) {
                error_log("Queued push notification for $userType: $userId (subscription {$sub['id']})");
            }
            
        } catch (Exception $e) {
            error_log("Failed to queue notification for subscription {$sub['id']}: " . $e->getMessage());
            $failures[] = [
                'subscription_id' => $sub['id'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Flush and send all queued notifications
    try {
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            
            if ($report->isSuccess()) {
                $successCount++;
                
                // Update last_used_at
                $pdo->prepare("
                    UPDATE push_subscriptions 
                    SET last_used_at = NOW() 
                    WHERE endpoint = ?
                ")->execute([$endpoint]);
                
            } else {
                $reason = $report->getReason();
                error_log("Push notification failed for endpoint: $reason");
                
                // Check if subscription is expired or invalid
                if ($report->isSubscriptionExpired() || $report->getStatusCode() === 410) {
                    error_log("Removing expired subscription: $endpoint");
                    $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?")
                        ->execute([$endpoint]);
                }
                
                $failures[] = [
                    'endpoint' => $endpoint,
                    'error' => $reason,
                    'status_code' => $report->getStatusCode()
                ];
            }
        }
    } catch (Exception $e) {
        error_log("WebPush flush error: " . $e->getMessage());
        $failures[] = ['error' => $e->getMessage()];
    }
    
    error_log("Push notification sent: $successCount success, " . count($failures) . " failures");
    
    return [
        'success' => $successCount,
        'failures' => $failures,
        'total' => count($subscriptions)
    ];
}

/**
 * Create in-app notification in database
 * 
 * @param string $userId User ID (driver_id or officer_id)
 * @param string $userType 'driver' or 'officer'
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $notificationType Type: message_reply, status_change, deadline_reminder, announcement, general
 * @param array|null $metadata Additional data as JSON
 * @param string|null $appNo Related application number
 * @return int|false Notification ID if successful, false on failure
 */
function create_in_app_notification($userId, $userType, $title, $message, $notificationType = 'general', $metadata = null, $appNo = null) {
    // Only drivers have in-app notifications in current schema
    if ($userType !== 'driver') {
        error_log("In-app notifications only supported for drivers currently");
        return false;
    }
    
    $pdo = get_pdo();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications 
            (driver_id, app_no, title, message, notification_type, metadata, is_read)
            VALUES (:driver_id, :app_no, :title, :message, :notification_type, :metadata, 0)
        ");
        
        $stmt->execute([
            'driver_id' => $userId,
            'app_no' => $appNo,
            'title' => $title,
            'message' => $message,
            'notification_type' => $notificationType,
            'metadata' => $metadata ? json_encode($metadata) : null
        ]);
        
        $notificationId = (int) $pdo->lastInsertId();
        
        error_log("Created in-app notification for driver $userId: $title (ID: $notificationId)");
        
        return $notificationId;
        
    } catch (PDOException $e) {
        error_log("Failed to create in-app notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Unified notification trigger - sends both push and in-app notifications
 * 
 * @param string $userId User ID
 * @param string $userType 'driver' or 'officer'
 * @param string $notificationType Type of notification
 * @param array $data Notification data with keys: title, message, url, appNo, metadata
 * @return array Result with push and in-app results
 */
function trigger_notification($userId, $userType, $notificationType, $data) {
    $title = $data['title'] ?? 'Notification';
    $message = $data['message'] ?? '';
    $url = $data['url'] ?? '/';
    $appNo = $data['appNo'] ?? null;
    $metadata = $data['metadata'] ?? [];
    
    $results = [
        'push' => null,
        'in_app' => null,
        'user_id' => $userId,
        'user_type' => $userType,
        'type' => $notificationType
    ];
    
    // Send push notification
    $results['push'] = send_push_notification(
        $userId,
        $userType,
        $title,
        $message,
        $url,
        ['tag' => $notificationType]
    );
    
    // Create in-app notification (only for drivers)
    if ($userType === 'driver') {
        $results['in_app'] = create_in_app_notification(
            $userId,
            $userType,
            $title,
            $message,
            $notificationType,
            $metadata,
            $appNo
        );
    }
    
    return $results;
}

/**
 * Send notification to all officers
 * 
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string|null $url URL to open
 * @param string $notificationType Notification type
 * @return array Summary of results
 */
function notify_all_officers($title, $message, $url = null, $notificationType = 'general') {
    $pdo = get_pdo();
    
    // Get all active officers
    $stmt = $pdo->query("SELECT officer_id FROM officers WHERE is_active = 1");
    $officers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results = [
        'total_officers' => count($officers),
        'notifications_sent' => 0,
        'failures' => []
    ];
    
    foreach ($officers as $officerId) {
        $result = send_push_notification(
            $officerId,
            'officer',
            $title,
            $message,
            $url,
            ['tag' => $notificationType]
        );
        
        if ($result['success'] > 0) {
            $results['notifications_sent']++;
        } else {
            $results['failures'][] = $officerId;
        }
    }
    
    return $results;
}

/**
 * Send notification to all drivers
 * 
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string|null $url URL to open
 * @param string $notificationType Notification type
 * @param bool $createInApp Also create in-app notifications
 * @return array Summary of results
 */
function notify_all_drivers($title, $message, $url = null, $notificationType = 'general', $createInApp = true) {
    $pdo = get_pdo();
    
    // Get all active drivers
    $stmt = $pdo->query("SELECT driver_id FROM drivers WHERE is_archived = 0");
    $drivers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results = [
        'total_drivers' => count($drivers),
        'push_sent' => 0,
        'in_app_created' => 0,
        'failures' => []
    ];
    
    foreach ($drivers as $driverId) {
        // Send push notification
        $pushResult = send_push_notification(
            $driverId,
            'driver',
            $title,
            $message,
            $url,
            ['tag' => $notificationType]
        );
        
        if ($pushResult['success'] > 0) {
            $results['push_sent']++;
        }
        
        // Create in-app notification
        if ($createInApp) {
            $inAppResult = create_in_app_notification(
                $driverId,
                'driver',
                $title,
                $message,
                $notificationType
            );
            
            if ($inAppResult) {
                $results['in_app_created']++;
            } else {
                $results['failures'][] = $driverId;
            }
        }
    }
    
    return $results;
}
