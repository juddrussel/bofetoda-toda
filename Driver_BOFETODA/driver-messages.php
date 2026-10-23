<?php

require 'driver-database.php';
require 'driver-template.php';

$driver = require_driver();
$driver_id = $driver['id'];

// Handle form submission
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
            // Send message
            $result = send_message_to_officers($driver_id, $message_text, $uploaded_files);
            
            if ($result['success']) {
                $success_message = 'Message sent successfully!';
                // Redirect to clear POST data
                header('Location: driver-messages.php?sent=1');
                exit;
            } else {
                $error_message = $result['error'] ?? 'Failed to send message. Please try again.';
            }
        }
    }
}

// Check for success from redirect
if (isset($_GET['sent']) && $_GET['sent'] == '1') {
    $success_message = 'Message sent successfully!';
}

// Get all messages for this driver
$messages = get_my_messages($driver_id);

// Mark all officer replies as read
mark_messages_as_read($driver_id);

render_header([
    'title' => 'Messages',
    'topbar_mode' => 'normal',
    'topbar_heading' => 'Messages to Officers',
]);
?>

<style>
.messages-container {
    max-width: 800px;
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
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-card);
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
    color: var(--color-text-muted);
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
    align-self: flex-end;
    background: #e3f2fd;
    border: 1px solid #90caf9;
}

.message-officer {
    align-self: flex-start;
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

.message-form-box {
    background: white;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-card);
    padding: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-text);
}

.form-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
    margin-bottom: 12px;
}

.form-textarea:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
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
    border: 1px solid var(--color-border);
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
    color: var(--color-text-muted);
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
    background: #e3f2fd;
    border: 1px solid #90caf9;
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
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-send:hover:not(:disabled) {
    background: #1565c0;
}

.btn-send:disabled {
    background: #ccc;
    cursor: not-allowed;
}

@media (max-width: 600px) {
    .messages-container {
        padding: 12px;
    }
    
    .message-bubble {
        max-width: 85%;
    }
}
</style>

<div class="messages-container">
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            ✓ <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            ✗ <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- Messages Display -->
    <div class="messages-box" id="messagesBox">
        <?php if (empty($messages)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">💬</div>
                <div class="empty-state-text">No messages yet. Send a message to officers below!</div>
            </div>
        <?php else: ?>
            <div class="messages-list">
                <?php foreach ($messages as $msg): ?>
                    <div class="message-bubble <?= $msg['sender_type'] === 'driver' ? 'message-driver' : 'message-officer' ?>">
                        <div class="message-sender">
                            <?php if ($msg['sender_type'] === 'driver'): ?>
                                You
                            <?php else: ?>
                                Officer <?= htmlspecialchars($msg['officer_name'] ?? 'Unknown') ?>
                            <?php endif; ?>
                        </div>
                        <div class="message-text"><?= nl2br(htmlspecialchars($msg['message_text'])) ?></div>
                        
                        <?php if (!empty($msg['attachments'])): ?>
                            <div class="message-attachments">
                                <?php foreach ($msg['attachments'] as $att): ?>
                                    <a href="download-attachment.php?id=<?= $att['id'] ?>" 
                                       class="attachment-item" 
                                       target="_blank">
                                        <span class="attachment-icon"><?= get_file_icon($att['file_name']) ?></span>
                                        <div class="attachment-info">
                                            <span class="attachment-name"><?= htmlspecialchars($att['file_name']) ?></span>
                                            <span class="attachment-size"><?= format_file_size($att['file_size']) ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="message-time"><?= format_message_time($msg['sent_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Message Form -->
    <div class="message-form-box">
        <form method="post" enctype="multipart/form-data" id="messageForm">
            <label class="form-label" for="messageText">Send a Message to Officers</label>
            <textarea 
                name="message_text" 
                id="messageText" 
                class="form-textarea" 
                placeholder="Type your message here..."
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
                <button type="submit" class="btn-send">Send Message</button>
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

<?php render_footer('messages'); ?>
