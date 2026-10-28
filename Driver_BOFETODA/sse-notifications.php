<?php
/**
 * Server-Sent Events (SSE) Endpoint for Real-Time Notifications
 * BOFETODA TODA Management System
 * 
 * This endpoint streams real-time notifications to drivers who are online.
 * It checks for new notifications every few seconds and sends them to the client.
 */

require_once __DIR__ . '/driver-database.php';
require_login();

$driver = current_driver();
if (!$driver) {
    http_response_code(401);
    exit('Unauthorized');
}

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Prevent PHP from buffering output
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

// Disable time limit for this script
set_time_limit(0);

// Get driver ID
$driverId = $driver['driver_id'];

// Track last notification ID to avoid duplicates
$lastNotificationId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;

// Function to send SSE message
function sendSSE($id, $event, $data) {
    echo "id: $id\n";
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// Keep connection alive and check for new notifications
$startTime = time();
$maxDuration = 300; // 5 minutes max connection time

while (true) {
    // Check if connection is still alive
    if (connection_aborted()) {
        break;
    }
    
    // Check if we've exceeded max duration
    if ((time() - $startTime) > $maxDuration) {
        sendSSE(0, 'reconnect', ['message' => 'Connection timeout, please reconnect']);
        break;
    }
    
    try {
        $pdo = get_pdo();
        
        // Check for new notifications
        $stmt = $pdo->prepare(
            "SELECT notification_id, title, message, created_at, is_read
             FROM notifications
             WHERE driver_id = :driver_id AND notification_id > :last_id
             ORDER BY notification_id ASC
             LIMIT 10"
        );
        $stmt->execute([
            'driver_id' => $driverId,
            'last_id' => $lastNotificationId
        ]);
        $newNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Send new notifications
        foreach ($newNotifications as $notification) {
            sendSSE(
                $notification['notification_id'],
                'notification',
                [
                    'id' => $notification['notification_id'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'created_at' => $notification['created_at'],
                    'is_read' => (bool)$notification['is_read'],
                    'timestamp' => time()
                ]
            );
            $lastNotificationId = $notification['notification_id'];
        }
        
        // Check for application status changes
        $stmt = $pdo->prepare(
            "SELECT app_no, status, updated_at
             FROM applications
             WHERE driver_id = :driver_id
             ORDER BY updated_at DESC
             LIMIT 1"
        );
        $stmt->execute(['driver_id' => $driverId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($application) {
            // Check if status changed recently (within last 10 seconds)
            $updatedTime = strtotime($application['updated_at']);
            if ((time() - $updatedTime) < 10) {
                sendSSE(
                    time(),
                    'statusChange',
                    [
                        'app_no' => $application['app_no'],
                        'status' => $application['status'],
                        'timestamp' => time()
                    ]
                );
            }
        }
        
        // Send heartbeat every 30 seconds to keep connection alive
        if (time() % 30 === 0) {
            sendSSE(time(), 'heartbeat', ['timestamp' => time()]);
        }
        
    } catch (Exception $e) {
        error_log("SSE Error: " . $e->getMessage());
        sendSSE(0, 'error', ['message' => 'Server error']);
        break;
    }
    
    // Sleep for 2 seconds before next check
    sleep(2);
}

// Close connection
exit();
