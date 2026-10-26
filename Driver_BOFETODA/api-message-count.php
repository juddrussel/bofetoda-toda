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
        $hasFiles = isset($_FILES['attachments']) && !empty($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name']);
        
        if (empty($message) && !$hasFiles) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Message or attachment required'
            ]);
            exit;
        }
        
        try {
            $pdo = get_pdo();
            $pdo->beginTransaction();
            
            // Insert message
            $stmt = $pdo->prepare(
                "INSERT INTO driver_messages (driver_id, sender_type, sender_id, message_text, has_attachments, has_reply, sent_at) 
                 VALUES (:driver_id, 'driver', :sender_id, :message_text, :has_attachments, 0, NOW())"
            );
            $stmt->execute([
                'driver_id' => $driver_id,
                'sender_id' => $driver_id,
                'message_text' => $message,
                'has_attachments' => $hasFiles ? 1 : 0
            ]);
            
            $messageId = $pdo->lastInsertId();
            
            // Handle file uploads if any
            if ($hasFiles) {
                require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';
                
                $uploadedCount = 0;
                $files = $_FILES['attachments'];
                $fileCount = count($files['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    // Skip if no file or error
                    if (!isset($files['error'][$i]) || $files['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    
                    $fileName = $files['name'][$i];
                    $fileTmpPath = $files['tmp_name'][$i];
                    $fileSize = $files['size'][$i];
                    $fileType = $files['type'][$i];
                    
                    // Validate file size (max 5MB)
                    if ($fileSize > 5 * 1024 * 1024) {
                        continue;
                    }
                    
                    // Try R2 upload first
                    try {
                        $r2Key = upload_message_attachment_to_r2($driver_id, $messageId, $fileTmpPath, $fileName);
                        
                        if ($r2Key) {
                            // Insert attachment record with R2 key
                            $stmt = $pdo->prepare(
                                "INSERT INTO message_attachments (message_id, file_name, file_size, file_type, r2_key, uploaded_at)
                                 VALUES (:message_id, :file_name, :file_size, :file_type, :r2_key, NOW())"
                            );
                            $stmt->execute([
                                'message_id' => $messageId,
                                'file_name' => $fileName,
                                'file_size' => $fileSize,
                                'file_type' => $fileType,
                                'r2_key' => $r2Key
                            ]);
                            $uploadedCount++;
                            continue;
                        }
                    } catch (Exception $e) {
                        error_log("R2 upload failed, trying local: " . $e->getMessage());
                    }
                    
                    // Fallback to local storage
                    $uploadDir = __DIR__ . '/uploads/message_attachments/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $safeFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                    $destPath = $uploadDir . $safeFileName;
                    
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $relativePath = 'Driver_BOFETODA/uploads/message_attachments/' . $safeFileName;
                        
                        $stmt = $pdo->prepare(
                            "INSERT INTO message_attachments (message_id, file_name, file_path, file_size, file_type, uploaded_at)
                             VALUES (:message_id, :file_name, :file_path, :file_size, :file_type, NOW())"
                        );
                        $stmt->execute([
                            'message_id' => $messageId,
                            'file_name' => $fileName,
                            'file_path' => $relativePath,
                            'file_size' => $fileSize,
                            'file_type' => $fileType
                        ]);
                        $uploadedCount++;
                    }
                }
                
                // Update has_attachments if uploads succeeded
                if ($uploadedCount > 0) {
                    $pdo->prepare("UPDATE driver_messages SET has_attachments = 1 WHERE id = ?")->execute([$messageId]);
                }
            }
            
            $pdo->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully'
            ]);
            exit;
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
                "SELECT 
                    dm.id as message_id, 
                    dm.sender_type, 
                    dm.message_text as message, 
                    dm.sent_at, 
                    dm.is_read,
                    dm.has_attachments,
                    GROUP_CONCAT(
                        CONCAT(ma.id, '|', ma.file_name, '|', ma.file_type)
                        SEPARATOR ':::'
                    ) as attachments
                 FROM driver_messages dm
                 LEFT JOIN message_attachments ma ON dm.id = ma.message_id
                 WHERE dm.driver_id = :driver_id
                 GROUP BY dm.id
                 ORDER BY dm.sent_at ASC
                 LIMIT 100"
            );
            $stmt->execute(['driver_id' => $driver_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse attachments
            foreach ($messages as &$msg) {
                $msg['attachment_list'] = [];
                if ($msg['has_attachments'] && !empty($msg['attachments'])) {
                    $attachments = explode(':::', $msg['attachments']);
                    foreach ($attachments as $att) {
                        $parts = explode('|', $att);
                        if (count($parts) === 3) {
                            $msg['attachment_list'][] = [
                                'id' => $parts[0],
                                'name' => $parts[1],
                                'type' => $parts[2]
                            ];
                        }
                    }
                }
                unset($msg['attachments']); // Remove raw string
            }
            
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
