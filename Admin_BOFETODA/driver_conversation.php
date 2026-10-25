<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
$officer_id = $officer['id'];
$officer_name = $officer['name'] ?? 'Officer';

// Get driver ID from query string
$driver_id = $_GET['driver_id'] ?? '';

if (empty($driver_id)) {
    header('Location: driver_inbox.php');
    exit;
}

// Get driver info
$driver = get_driver_info($driver_id);

if (!$driver) {
    header('Location: driver_inbox.php');
    exit;
}

// Handle form submission (officer reply)
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_text'])) {
    $message_text = trim($_POST['message_text']);
    
    if (empty($message_text)) {
        $error_message = 'Please enter a message.';
    } else {
        // Handle file uploads
        $uploaded_files = [];
        if (!empty($_FILES['attachments']['name'][0])) {
            $file_count = count($_FILES['attachments']['name']);
            
            if ($file_count > 3) {
                $error_message = 'You can upload a maximum of 3 files per message.';
            } else {
                // Prepare files array
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                        $uploaded_files[] = [
                            'name' => $_FILES['attachments']['name'][$i],
                            'type' => $_FILES['attachments']['type'][$i],
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                            'error' => $_FILES['attachments']['error'][$i],
                            'size' => $_FILES['attachments']['size'][$i]
                        ];
                    }
                }
            }
        }
        
        if (empty($error_message)) {
            // Send officer reply
            $result = send_driver_message($driver_id, 'officer', $officer_id, $message_text, $uploaded_files);
            
            if ($result !== false) {
                // Update reply status
                update_message_reply_status($driver_id);
                
                $success_message = 'Reply sent successfully!';
                // Redirect to clear POST data
                header('Location: driver_conversation.php?driver_id=' . urlencode($driver_id) . '&sent=1');
                exit;
            } else {
                $error_message = 'Failed to send reply. Please try again.';
            }
        }
    }
}

// Check for success from redirect
if (isset($_GET['sent']) && $_GET['sent'] == '1') {
    $success_message = 'Reply sent successfully!';
}

// Get all messages for this driver
$messages = get_driver_messages($driver_id);

// Mark all messages as read
mark_driver_messages_as_read($driver_id);

// Get driver initials for avatar
$nameParts = explode(' ', $driver['name']);
$initials = '';
if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1], 0, 1));
} else {
    $initials = strtoupper(substr($driver['name'], 0, 2));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Conversation with <?= e($driver['name']) ?> · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.conversation-header {
    padding: 20px 28px;
    background: white;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 16px;
}

.back-link {
    color: var(--navy);
    text-decoration: none;
    font-size: 24px;
    font-weight: 700;
    transition: opacity 0.2s;
}

.back-link:hover {
    opacity: 0.7;
}

.driver-header-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--navy);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    flex-shrink: 0;
}

.driver-header-info {
    flex: 1;
}

.driver-header-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 2px;
}

.driver-header-id {
    font-size: 13px;
    color: var(--text-muted);
}

.conversation-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.messages-box {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: 0 1px 3px rgba(0,0,0,.1);
    margin-bottom: 20px;
    min-height: 400px;
    max-height: 500px;
    overflow-y: auto;
    padding: 20px;
}

.messages-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state-text {
    font-size: 16px;
    font-weight: 500;
}

.message-bubble {
    display: flex;
    flex-direction: column;
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 12px;
    word-wrap: break-word;
    position: relative;
}

.message-driver {
    align-self: flex-start;
    background: #e3f2fd;
    border: 1px solid #90caf9;
}

.message-officer {
    align-self: flex-end;
    background: #f5f5f5;
    border: 1px solid #e0e0e0;
}

.message-sender {
    font-size: 12px;
    font-weight: 600;
    color: #555;
    margin-bottom: 4px;
}

.message-text {
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 6px;
    white-space: pre-wrap;
}

.message-attachments {
    margin-top: 8px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.attachment-image {
    display: block;
    max-width: 100%;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
}

.attachment-image:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.attachment-image img {
    width: 100%;
    max-width: 300px;
    height: auto;
    display: block;
    border-radius: 8px 8px 0 0;
}

.image-filename {
    display: block;
    padding: 6px 8px;
    font-size: 11px;
    color: #555;
    background: rgba(255, 255, 255, 0.95);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    word-break: break-word;
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    font-size: 13px;
    text-decoration: none;
    color: #1976d2;
    transition: background 0.2s;
}

.attachment-item:hover {
    background: rgba(255, 255, 255, 0.9);
}

.attachment-icon {
    font-size: 18px;
}

.attachment-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.attachment-name {
    font-weight: 500;
    word-break: break-word;
}

.attachment-size {
    font-size: 11px;
    color: #666;
}

.message-time {
    font-size: 11px;
    color: #888;
    margin-top: 4px;
    text-align: right;
}

.reply-form-box {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: 0 1px 3px rgba(0,0,0,.1);
    padding: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text);
}

.form-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
    margin-bottom: 12px;
}

.form-textarea:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(22,35,61,.08);
}

.file-upload-wrapper {
    margin-bottom: 16px;
}

.file-upload-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #f5f5f5;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.file-upload-label:hover {
    background: #e8e8e8;
}

.file-upload-input {
    display: none;
}

.file-upload-info {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 6px;
}

.selected-files {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.selected-file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 6px;
    font-size: 13px;
}

.selected-file-name {
    font-weight: 500;
}

.remove-file-btn {
    background: none;
    border: none;
    color: #d32f2f;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
}

.btn-send {
    padding: 12px 32px;
    background: var(--navy);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-send:hover:not(:disabled) {
    opacity: 0.9;
}

.btn-send:disabled {
    background: #ccc;
    cursor: not-allowed;
}

@media (max-width: 600px) {
    .conversation-container {
        padding: 12px;
    }
    
    .conversation-header {
        padding: 16px;
    }
    
    .message-bubble {
        max-width: 85%;
    }
}
</style>
</head>
<body>
<?php render_sidebar('driver_inbox'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Driver Conversation'); ?>

  <div class="page">
    <a href="driver_inbox.php" class="back-link">←</a>
    <div class="driver-header-avatar"><?= e($initials) ?></div>
    <div class="driver-header-info">
        <div class="driver-header-name"><?= e($driver['name']) ?></div>
        <div class="driver-header-id">Driver ID: <?= e($driver['driver_id']) ?></div>
    </div>
</div>

<div class="conversation-container">
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            ✓ <?= e($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            ✗ <?= e($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Messages Display -->
    <div class="messages-box" id="messagesBox">
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">💬</div>
                <div class="empty-state-text">No messages in this conversation yet.</div>
            </div>
        <?php else: ?>
            <div class="messages-list">
                <?php foreach ($messages as $msg): ?>
                    <div class="message-bubble <?= $msg['sender_type'] === 'driver' ? 'message-driver' : 'message-officer' ?>">
                        <div class="message-sender">
                            <?php if ($msg['sender_type'] === 'driver'): ?>
                                <?= e($driver['name']) ?>
                            <?php else: ?>
                                <?= e($msg['officer_name'] ?? 'Officer') ?>
                            <?php endif; ?>
                        </div>
                        <div class="message-text"><?= nl2br(e($msg['message_text'])) ?></div>
                        
                        <?php if (!empty($msg['attachments'])): ?>
                            <div class="message-attachments">
                                <?php foreach ($msg['attachments'] as $att): 
                                    $isImage = is_image_file_admin($att['file_name']);
                                ?>
                                    <?php if ($isImage): ?>
                                        <a href="download_message_attachment.php?id=<?= $att['id'] ?>" 
                                           class="attachment-image" 
                                           target="_blank">
                                            <img src="download_message_attachment.php?id=<?= $att['id'] ?>" 
                                                 alt="<?= e($att['file_name']) ?>"
                                                 loading="lazy">
                                            <span class="image-filename"><?= e($att['file_name']) ?></span>
                                        </a>
                                    <?php else: ?>
                                        <a href="download_message_attachment.php?id=<?= $att['id'] ?>" 
                                           class="attachment-item" 
                                           target="_blank">
                                            <span class="attachment-icon"><?= get_file_icon_admin($att['file_name']) ?></span>
                                            <div class="attachment-info">
                                                <span class="attachment-name"><?= e($att['file_name']) ?></span>
                                                <span class="attachment-size"><?= format_file_size_admin($att['file_size']) ?></span>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="message-time"><?= format_relative_time($msg['sent_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reply Form -->
    <div class="reply-form-box">
        <form method="post" enctype="multipart/form-data" id="replyForm">
            <label class="form-label" for="messageText">Send a Reply</label>
            <textarea 
                name="message_text" 
                id="messageText" 
                class="form-textarea" 
                placeholder="Type your reply here..."
                required></textarea>
            
            <div class="file-upload-wrapper">
                <label class="file-upload-label" for="fileInput">
                    📎 Attach Files
                </label>
                <input 
                    type="file" 
                    name="attachments[]" 
                    id="fileInput" 
                    class="file-upload-input"
                    multiple
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                <div class="file-upload-info">
                    Max 3 files • 5MB each • PDF, DOC, DOCX, JPG, PNG only
                </div>
                <div id="selectedFiles" class="selected-files"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-send">Send Reply</button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const messagesBox = document.getElementById('messagesBox');
    if (messagesBox) {
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }
    
    // File upload preview
    const fileInput = document.getElementById('fileInput');
    const selectedFilesDiv = document.getElementById('selectedFiles');
    
    fileInput.addEventListener('change', function() {
        selectedFilesDiv.innerHTML = '';
        
        if (this.files.length > 3) {
            alert('Maximum 3 files allowed');
            this.value = '';
            return;
        }
        
        Array.from(this.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'selected-file-item';
            
            const fileName = document.createElement('span');
            fileName.className = 'selected-file-name';
            fileName.textContent = file.name;
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file-btn';
            removeBtn.textContent = '×';
            removeBtn.type = 'button';
            removeBtn.onclick = function() {
                // Create a new FileList without this file
                const dt = new DataTransfer();
                Array.from(fileInput.files).forEach((f, i) => {
                    if (i !== index) {
                        dt.items.add(f);
                    }
                });
                fileInput.files = dt.files;
                
                // Trigger change event to update display
                fileInput.dispatchEvent(new Event('change'));
            };
            
            fileItem.appendChild(fileName);
            fileItem.appendChild(removeBtn);
            selectedFilesDiv.appendChild(fileItem);
        });
    });
});
</script>

  </div><!-- /.page -->
</div><!-- /.main-col -->

<?php render_shared_script(); ?>
</body>
</html>

