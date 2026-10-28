<?php
/**
 * Server-Sent Events (SSE) Endpoint for Real-Time Notifications (Officers)
 * BOFETODA TODA Management System
 * 
 * This endpoint streams real-time notifications to officers who are online.
 */

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
if (!$officer) {
    http_response_code(401);
    exit('Unauthorized');
}

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Prevent PHP from buffering output
if (ob_get_level()) ob_end_clean();
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

set_time_limit(0);

$officerId = $officer['id'];
$lastNotificationId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;

// Track sent notifications in memory
$sentNotifications = [];

function sendSSE($id, $event, $data) {
    echo "id: $id\n";
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

$startTime = time();
$maxDuration = 300; // 5 minutes

while (true) {
    if (connection_aborted()) {
        break;
    }
    
    if ((time() - $startTime) > $maxDuration) {
        sendSSE(0, 'reconnect', ['message' => 'Connection timeout, please reconnect']);
        break;
    }
    
    try {
        $pdo = get_pdo();
        
        // Check for new applications (pending/for approval)
        $stmt = $pdo->query(
            "SELECT COUNT(*) as count, MAX(submitted_at) as last_submitted
             FROM applications
             WHERE status IN ('Pending', 'For Approval')
             AND submitted_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['count'] > 0 && $result['last_submitted']) {
            // Create unique key based on count and last submission time
            $currentKey = $result['count'] . '_' . strtotime($result['last_submitted']);
            
            if (!isset($sentNotifications[$currentKey])) {
                sendSSE(
                    time(),
                    'newApplication',
                    [
                        'count' => $result['count'],
                        'message' => $result['count'] . ' new application(s) submitted',
                        'timestamp' => time()
                    ]
                );
                
                $sentNotifications[$currentKey] = time();
                
                // Clean up old entries
                foreach ($sentNotifications as $key => $timestamp) {
                    if ((time() - $timestamp) > 3600) {
                        unset($sentNotifications[$key]);
                    }
                }
            }
        }
        
        // Heartbeat
        if (time() % 30 === 0) {
            sendSSE(time(), 'heartbeat', ['timestamp' => time()]);
        }
        
    } catch (Exception $e) {
        error_log("SSE Error (Officer): " . $e->getMessage());
        sendSSE(0, 'error', ['message' => 'Server error']);
        break;
    }
    
    sleep(2);
}

exit();
