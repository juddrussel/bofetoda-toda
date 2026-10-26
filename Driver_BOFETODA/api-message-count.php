<?php
/**
 * API endpoint for driver messaging
 * - Get unread reply count
 * - Get all messages
 * - Send new message
 */

require 'driver-database.php';

// Verify driver is logged in
try {
    $driver = require_driver();
    $driver_id = $driver['id'];
    
    // Handle POST request - send message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Message cannot be empty'
            ]);
            exit;
        }
        
        try {
            $pdo = get_pdo();
            $stmt = $pdo->prepare(
                "INSERT INTO driver_messages (driver_id, sender_type, sender_id, message_text, has_reply, sent_at) 
                 VALUES (:driver_id, 'driver', :sender_id, :message_text, 0, NOW())"
            );
            $stmt->execute([
                'driver_id' => $driver_id,
                'sender_id' => $driver_id,
                'message_text' => $message
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully'
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log("Failed to send message: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Handle GET request - fetch messages
    if (isset($_GET['get_messages'])) {
        try {
            $pdo = get_pdo();
            $stmt = $pdo->prepare(
                "SELECT id as message_id, sender_type, message_text as message, sent_at, is_read
                 FROM driver_messages
                 WHERE driver_id = :driver_id
                 ORDER BY sent_at ASC
                 LIMIT 100"
            );
            $stmt->execute(['driver_id' => $driver_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Mark officer messages as read
            $pdo->prepare(
                "UPDATE driver_messages 
                 SET is_read = 1 
                 WHERE driver_id = :driver_id AND sender_type = 'officer' AND is_read = 0"
            )->execute(['driver_id' => $driver_id]);
            
            // When officer sends a message, mark all previous driver messages as replied
            $lastOfficerMsg = $pdo->prepare(
                "SELECT id FROM driver_messages 
                 WHERE driver_id = :driver_id AND sender_type = 'officer' 
                 ORDER BY sent_at DESC LIMIT 1"
            );
            $lastOfficerMsg->execute(['driver_id' => $driver_id]);
            if ($lastOfficerMsg->fetchColumn()) {
                $pdo->prepare(
                    "UPDATE driver_messages 
                     SET has_reply = 1 
                     WHERE driver_id = :driver_id AND sender_type = 'driver' AND has_reply = 0"
                )->execute(['driver_id' => $driver_id]);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            exit;
            
        } catch (Exception $e) {
            error_log("Failed to fetch messages: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'messages' => []
            ]);
            exit;
        }
    }
    
    // Default: Get unread reply count
    $count = get_my_unread_reply_count($driver_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
}
