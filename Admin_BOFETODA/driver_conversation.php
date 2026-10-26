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

<!-- Tailwind CSS CDN with forms and container queries plugins -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

<!-- Font Awesome for clean operational icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- Google Fonts: Inter -->
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Tailwind Configuration -->
<script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
          colors: {
            toda: {
              navy: '#16233d',
              navyLight: '#1e293b',
              gold: '#f59e0b',
              goldHover: '#d97706',
              surface: '#f8fafc',
              border: '#e2e8f0',
            }
          }
        }
      }
    }
</script>

<!-- Style Block: Custom Layout Enhancements -->
<style data-purpose="custom-scrollbars-and-shadows">
    /* Subtle custom scrollbar for message thread */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #f1f5f9;
    }
    ::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
    
    /* Override main-col background for Tailwind */
    .main-col {
      background: #f1f5f9 !important;
    }
    
    /* Ensure sidebar stays on top */
    .sidebar {
      position: relative;
      z-index: 100 !important;
    }
    
    /* Scope Tailwind to conversation content only */
    .conversation-content * {
      font-family: 'Inter', sans-serif !important;
    }
</style>
</head>

<body>
<?php render_sidebar('driver_inbox'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Driver Conversation'); ?>
  
  <style>
    /* Make topbar sticky */
    .topbar {
      position: sticky !important;
      top: 0 !important;
      z-index: 50 !important;
      background: white !important;
    }
  </style>

  <div class="page" style="background: #f1f5f9;">
    
    <div class="conversation-content">
    <!-- BEGIN: MainContentContainer -->
    <div class="max-w-5xl mx-auto flex flex-col gap-6">

      <!-- BEGIN: DriverProfileHeaderCard -->
      <section class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200" data-purpose="driver-summary-header">
        <div class="flex items-center justify-between mb-4">
          <a class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-toda-navy transition-colors py-1.5 px-3 rounded-lg hover:bg-slate-100" href="driver_inbox.php">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Back to Driver Inbox</span>
          </a>
        </div>
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <!-- Driver Profile Info -->
          <div class="flex items-start sm:items-center space-x-4">
            <!-- Avatar Initials -->
            <div class="relative shrink-0">
              <div class="w-14 h-14 rounded-2xl bg-toda-navy text-white flex items-center justify-center font-bold text-xl shadow-inner border border-slate-700">
                <?= e($initials) ?>
              </div>
              <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full" title="Active driver"></span>
            </div>
            
            <!-- Names & Meta -->
            <div>
              <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-xl font-bold text-slate-900 leading-tight"><?= e($driver['name']) ?></h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                  Active Driver
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-y-1 gap-x-4 mt-1.5 text-xs sm:text-sm text-slate-500 font-medium">
                <span class="flex items-center gap-1.5">
                  <i class="fa-solid fa-id-badge text-slate-400"></i>
                  <span class="text-slate-700">Driver ID:</span> <?= e($driver['driver_id']) ?>
                </span>
                <span class="flex items-center gap-1.5">
                  <i class="fa-solid fa-motorcycle text-slate-400"></i>
                  <span class="text-slate-700">Body No:</span> <?= e($driver['body_no'] ?? 'N/A') ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- END: DriverProfileHeaderCard -->

      <?php if ($success_message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-medium">
          <i class="fa-solid fa-check-circle mr-2"></i><?= e($success_message) ?>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm font-medium">
          <i class="fa-solid fa-exclamation-circle mr-2"></i><?= e($error_message) ?>
        </div>
      <?php endif; ?>

      <!-- BEGIN: ConversationThread -->
      <section class="flex flex-col gap-5" data-purpose="messages-timeline-thread">
        
        <?php if (empty($messages)): ?>
          <!-- Empty State -->
          <div class="bg-white rounded-2xl p-12 text-center border border-slate-200">
            <div class="text-5xl mb-4">💬</div>
            <div class="text-slate-600 font-medium">No messages in this conversation yet.</div>
          </div>
        <?php else: ?>
          
          <!-- Date Divider -->
          <div class="flex items-center justify-center my-1" data-purpose="timeline-date-divider">
            <span class="bg-slate-200/80 text-slate-600 text-xs font-medium px-4 py-1 rounded-full uppercase tracking-wider shadow-sm">
              <?= date('l • F j, Y') ?>
            </span>
          </div>

          <?php foreach ($messages as $msg): ?>
            <?php if ($msg['sender_type'] === 'driver'): ?>
              <!-- Message: Driver Inbound -->
              <article class="flex items-start gap-3 max-w-2xl self-start group" data-purpose="driver-message-item">
                <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold shrink-0 mt-1 shadow-sm">
                  <?= e($initials) ?>
                </div>
                <div class="space-y-1.5">
                  <div class="flex items-center gap-2 px-1">
                    <span class="text-xs font-bold text-slate-800"><?= e($driver['name']) ?></span>
                    <span class="text-[11px] text-slate-400"><?= format_relative_time($msg['sent_at']) ?></span>
                  </div>
                  
                  <!-- Card Bubble -->
                  <div class="bg-white rounded-2xl rounded-tl-sm border border-slate-200 p-3 shadow-sm hover:shadow transition-shadow max-w-md">
                    <?php if (!empty($msg['message_text'])): ?>
                      <p class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap"><?= nl2br(e($msg['message_text'])) ?></p>
                    <?php endif; ?>
                    
                    <!-- Attachments -->
                    <?php if (!empty($msg['attachments'])): ?>
                      <div class="space-y-2 <?= !empty($msg['message_text']) ? 'mt-2' : '' ?>">
                        <?php foreach ($msg['attachments'] as $att): 
                          $isImage = is_image_file_admin($att['file_name']);
                        ?>
                          <?php if ($isImage): ?>
                            <!-- Image Attachment -->
                            <div class="relative overflow-hidden rounded-xl bg-slate-100 border border-slate-200 group/img">
                              <img alt="<?= e($att['file_name']) ?>" 
                                   class="w-full h-52 object-cover object-center transition duration-200 group-hover/img:scale-105" 
                                   src="download_message_attachment.php?id=<?= $att['id'] ?>"
                                   loading="lazy">
                              <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <a class="px-3 py-1.5 bg-white/95 rounded-lg text-xs font-semibold text-slate-800 hover:bg-white shadow" 
                                   href="download_message_attachment.php?id=<?= $att['id'] ?>" 
                                   target="_blank">
                                  <i class="fa-solid fa-magnifying-glass-plus mr-1"></i> Preview
                                </a>
                                <a class="px-3 py-1.5 bg-white/95 rounded-lg text-xs font-semibold text-slate-800 hover:bg-white shadow" 
                                   download="" 
                                   href="download_message_attachment.php?id=<?= $att['id'] ?>">
                                  <i class="fa-solid fa-download mr-1"></i> Download
                                </a>
                              </div>
                            </div>
                            <!-- Attachment Metadata -->
                            <div class="flex items-center justify-between px-1 text-xs">
                              <div class="flex items-center gap-1.5 text-slate-700 font-medium">
                                <i class="fa-regular fa-image text-slate-400"></i>
                                <span><?= e($att['file_name']) ?></span>
                              </div>
                              <span class="text-slate-400 text-[11px]"><?= format_file_size_admin($att['file_size']) ?></span>
                            </div>
                          <?php else: ?>
                            <!-- Document Attachment -->
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-slate-100/80 transition-colors">
                              <div class="flex items-center space-x-3 truncate">
                                <div class="w-10 h-10 rounded-lg bg-red-100 border border-red-200 text-red-600 flex items-center justify-center shrink-0">
                                  <i class="fa-solid fa-file-pdf text-lg"></i>
                                </div>
                                <div class="truncate text-left">
                                  <p class="text-xs font-semibold text-slate-800 truncate" title="<?= e($att['file_name']) ?>">
                                    <?= e($att['file_name']) ?>
                                  </p>
                                  <p class="text-[11px] text-slate-400"><?= format_file_size_admin($att['file_size']) ?> • Document</p>
                                </div>
                              </div>
                              <a class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-white transition-all shrink-0 ml-2" 
                                 href="download_message_attachment.php?id=<?= $att['id'] ?>" 
                                 title="Download document">
                                <i class="fa-solid fa-cloud-arrow-down text-sm"></i>
                              </a>
                            </div>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            
            <?php else: ?>
              <!-- Message: Admin Outbound Response -->
              <article class="flex flex-row-reverse items-start gap-3 max-w-2xl self-end group" data-purpose="admin-response-item">
                <div class="w-8 h-8 rounded-full bg-amber-500 text-slate-900 flex items-center justify-center text-xs font-black shrink-0 mt-1 shadow-sm">
                  HQ
                </div>
                <div class="space-y-1.5 text-right">
                  <div class="flex items-center justify-end gap-2 px-1">
                    <span class="text-[11px] text-slate-400"><?= format_relative_time($msg['sent_at']) ?></span>
                    <span class="text-xs font-bold text-slate-800"><?= e($msg['officer_name'] ?? 'Admin') ?></span>
                  </div>
                  
                  <!-- Message Bubble -->
                  <div class="bg-toda-navy text-white text-left rounded-2xl rounded-tr-sm p-4 shadow-sm text-sm leading-relaxed max-w-md">
                    <?= nl2br(e($msg['message_text'])) ?>
                    
                    <!-- Attachments for admin messages -->
                    <?php if (!empty($msg['attachments'])): ?>
                      <div class="space-y-2 mt-3">
                        <?php foreach ($msg['attachments'] as $att): 
                          $isImage = is_image_file_admin($att['file_name']);
                        ?>
                          <a href="download_message_attachment.php?id=<?= $att['id'] ?>" 
                             class="block p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors text-xs">
                            <i class="fa-solid <?= $isImage ? 'fa-image' : 'fa-file' ?> mr-1"></i>
                            <?= e($att['file_name']) ?>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  
                  <!-- Status Receipt -->
                  <div class="text-[11px] text-slate-400 pr-1 flex items-center justify-end gap-1">
                    <i class="fa-solid fa-check-double text-blue-500"></i>
                    <span>Delivered</span>
                  </div>
                </div>
              </article>
            <?php endif; ?>
          <?php endforeach; ?>
          
        <?php endif; ?>
        
      </section>
      <!-- END: ConversationThread -->

      <!-- BEGIN: MessageReplyComposer -->
      <section class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm mt-2 sticky bottom-0 z-20" data-purpose="composer-reply-panel" style="backdrop-filter: blur(8px); background: rgba(255, 255, 255, 0.95);">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-regular fa-comment-dots text-amber-500"></i>
            Send a Reply
          </h2>
          <span class="text-[11px] text-slate-400 hidden sm:inline-block">Replying as: <?= e($officer_name) ?></span>
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4" id="replyForm">
          <!-- Reply Text Box -->
          <div class="relative">
            <textarea 
              class="w-full text-sm rounded-xl border border-slate-300 focus:border-toda-navy focus:ring-1 focus:ring-toda-navy p-3.5 placeholder-slate-400 text-slate-800 resize-y" 
              id="messageText"
              name="message_text" 
              placeholder="Type your reply here... (Shift + Enter for new line)" 
              rows="4"
              required></textarea>
          </div>
          
          <!-- Attachment Bar & Quick Actions -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1 border-t border-slate-100">
            <!-- Attachment Details & Dropdown -->
            <div class="flex flex-wrap items-center gap-2">
              <!-- File Attach Trigger Button -->
              <label class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-colors">
                <i class="fa-solid fa-paperclip text-slate-500"></i>
                <span>Attach Files</span>
                <input 
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                  class="hidden" 
                  id="fileInput"
                  name="attachments[]"
                  multiple 
                  type="file">
              </label>
              <div id="selectedFiles" class="text-xs text-slate-600"></div>
            </div>
            
            <!-- Submission Controls -->
            <div class="flex items-center justify-end gap-2">
              <button class="px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors" type="reset">
                Clear
              </button>
              <button class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-toda-navy hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition-all shadow-sm hover:shadow" type="submit">
                <i class="fa-solid fa-paper-plane text-[11px] text-amber-400"></i>
                <span>Send Reply</span>
              </button>
            </div>
          </div>
          
          <!-- File Upload Helper Footnote -->
          <div class="flex items-center gap-1.5 text-[11px] text-slate-400 pt-0.5">
            <i class="fa-solid fa-circle-info text-[10px] text-slate-400"></i>
            <span>Max 3 files • 5MB each • Supported formats: PDF, DOC, DOCX, JPG, PNG only</span>
          </div>
        </form>
      </section>
      <!-- END: MessageReplyComposer -->

    </div>
    <!-- END: MainContentContainer -->
    
    </div><!-- /.conversation-content -->
  </div><!-- /.page -->
</div><!-- /.main-col -->

<script>
// File upload preview and auto-scroll to latest message
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll to latest message (bottom of conversation) - instant, no animation
    const lastMessage = document.querySelector('[data-purpose="messages-timeline-thread"] article:last-of-type');
    if (lastMessage) {
        // Instant scroll to bottom, no smooth animation
        lastMessage.scrollIntoView({ behavior: 'auto', block: 'end' });
    }
    
    // File upload preview
    const fileInput = document.getElementById('fileInput');
    const selectedFilesDiv = document.getElementById('selectedFiles');
    
    if (fileInput && selectedFilesDiv) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 3) {
                alert('Maximum 3 files allowed');
                this.value = '';
                selectedFilesDiv.textContent = '';
                return;
            }
            
            if (this.files.length > 0) {
                const fileNames = Array.from(this.files).map(f => f.name).join(', ');
                selectedFilesDiv.textContent = `${this.files.length} file(s) selected: ${fileNames}`;
            } else {
                selectedFilesDiv.textContent = '';
            }
        });
    }
});
</script>

<?php render_shared_script(); ?>
</body>
</html>>

