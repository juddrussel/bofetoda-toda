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
        $hasFiles = isset($_FILES['attachments']) && isset($_FILES['attachments']['name']) && is_array($_FILES['attachments']['name']) && !empty($_FILES['attachments']['name'][0]);
        
        error_log("=== MESSAGE SEND DEBUG ===");
        error_log("Message: " . $message);
        error_log("Has files: " . ($hasFiles ? 'yes' : 'no'));
        if ($hasFiles) {
            error_log("File count: " . count($_FILES['attachments']['name']));
        }
        
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
            error_log("Message ID: " . $messageId);
            
            // Handle file uploads if any
            if ($hasFiles) {
                require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';
                
                $uploadedCount = 0;
                $files = $_FILES['attachments'];
                $fileCount = count($files['name']);
                
                error_log("Processing $fileCount files...");
                
                for ($i = 0; $i < $fileCount; $i++) {
                    // Skip if no file or error
                    if (!isset($files['error'][$i]) || $files['error'][$i] !== UPLOAD_ERR_OK) {
                        error_log("File $i has upload error: " . ($files['error'][$i] ?? 'unknown'));
                        continue;
                    }
                    
                    $fileName = $files['name'][$i];
                    $fileTmpPath = $files['tmp_name'][$i];
                    $fileSize = $files['size'][$i];
                    $fileType = $files['type'][$i];
                    
                    error_log("Processing file: $fileName ($fileSize bytes, $fileType)");
                    
                    // Validate file size (max 5MB)
                    if ($fileSize > 5 * 1024 * 1024) {
                        error_log("File too large: $fileName");
                        continue;
                    }
                    
                    // Prepare file array for R2 function
                    $fileArray = [
                        'name' => $fileName,
                        'tmp_name' => $fileTmpPath,
                        'size' => $fileSize,
                        'type' => $fileType,
                        'error' => $files['error'][$i]
                    ];
                    
                    // Try R2 upload with fallback to local
                    try {
                        $uploadResult = upload_message_attachment_r2($driver_id, $messageId, $fileArray);
                        
                        if ($uploadResult && is_array($uploadResult)) {
                            error_log("Upload successful - Storage: " . $uploadResult['storage']);
                            
                            // Insert attachment record
                            if ($uploadResult['storage'] === 'r2') {
                                // R2 storage
                                $stmt = $pdo->prepare(
                                    "INSERT INTO message_attachments (message_id, file_name, file_size, file_type, r2_key, uploaded_at)
                                     VALUES (:message_id, :file_name, :file_size, :file_type, :r2_key, NOW())"
                                );
                                $stmt->execute([
                                    'message_id' => $messageId,
                                    'file_name' => $fileName,
                                    'file_size' => $fileSize,
                                    'file_type' => $fileType,
                                    'r2_key' => $uploadResult['path']
                                ]);
                            } else {
                                // Local storage
                                $stmt = $pdo->prepare(
                                    "INSERT INTO message_attachments (message_id, file_name, file_path, file_size, file_type, uploaded_at)
                                     VALUES (:message_id, :file_name, :file_path, :file_size, :file_type, NOW())"
                                );
                                $stmt->execute([
                                    'message_id' => $messageId,
                                    'file_name' => $fileName,
                                    'file_path' => $uploadResult['path'],
                                    'file_size' => $fileSize,
                                    'file_type' => $fileType
                                ]);
                            }
                            
                            $uploadedCount++;
                            error_log("Attachment saved to database");
                        } else {
                            error_log("Upload failed for: $fileName");
                        }
                        
                    } catch (Exception $e) {
                        error_log("Upload exception for $fileName: " . $e->getMessage());
                    }
                }
                
                error_log("Uploaded $uploadedCount files");
                
                // Update has_attachments if uploads succeeded
                if ($uploadedCount > 0) {
                    $pdo->prepare("UPDATE driver_messages SET has_attachments = 1 WHERE id = ?")->execute([$messageId]);
                }
            }
            
            $pdo->commit();
            error_log("=== MESSAGE SENT SUCCESSFULLY ===");
            
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
            error_log("=== MESSAGE SEND FAILED ===");
            error_log("Error: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
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
