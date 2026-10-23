<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
$officer_id = $officer['id'];

// Get list of drivers with messages
$drivers_with_messages = get_drivers_with_messages();

// Get unread count for badge
$unread_count = get_unread_message_count();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Inbox · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.inbox-container {
    max-width: 100%;
}

.inbox-empty {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

.inbox-empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.inbox-empty-text {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.inbox-empty-subtitle {
    font-size: 14px;
    color: var(--text-muted);
}

.inbox-list {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}

.inbox-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
    position: relative;
}

.inbox-item:last-child {
    border-bottom: none;
}

.inbox-item:hover {
    background: var(--bg);
}

.inbox-item.has-unread {
    background: #f0f9ff;
}

.inbox-item.has-unread:hover {
    background: #e0f2fe;
}

.driver-avatar {
    width: 50px;
    height: 50px;
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

.driver-info {
    flex: 1;
    min-width: 0;
}

.driver-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.unread-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    background: #dc2626;
    color: white;
    font-size: 11px;
    font-weight: 700;
    border-radius: 999px;
}

.message-preview {
    font-size: 14px;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.message-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    flex-shrink: 0;
}

.message-time {
    font-size: 12px;
    color: var(--text-muted);
    white-space: nowrap;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.needs-reply {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.replied {
    background: #d1fae5;
    color: #065f46;
}

.attachment-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--text-muted);
    margin-left: 4px;
}

@media (max-width: 600px) {
    .inbox-container {
        padding: 12px;
    }
    
    .driver-avatar {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
    
    .message-meta {
        align-items: flex-start;
    }
}
</style>
</head>
<body>
<?php render_sidebar('driver_inbox'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Driver Inbox'); ?>

  <div class="page">
    <div style="margin-bottom:20px;">
      <h2 style="font-size:26px; font-weight:800; color:var(--navy);">Driver Inbox</h2>
      <p style="font-size:13px; color:var(--text-muted); margin-top:4px;">
        Messages from drivers
        <?php if ($unread_count > 0): ?>
          · <strong><?= $unread_count ?></strong> unread message<?= $unread_count !== 1 ? 's' : '' ?>
        <?php endif; ?>
      </p>
    </div>
    <?php if (empty($drivers_with_messages)): ?>
        <div class="inbox-empty">
            <div class="inbox-empty-icon">📬</div>
            <div class="inbox-empty-text">No messages yet</div>
            <div class="inbox-empty-subtitle">When drivers send messages, they will appear here.</div>
        </div>
    <?php else: ?>
        <div class="inbox-list">
            <?php foreach ($drivers_with_messages as $item): ?>
                <?php
                $driver = $item['driver'];
                $lastMessage = $item['last_message'];
                $unreadCount = $item['unread_count'];
                $hasUnread = $unreadCount > 0;
                $needsReply = !$lastMessage['has_reply'] && $lastMessage['sender_type'] === 'driver';
                
                // Format time
                $messageTime = format_relative_time($lastMessage['sent_at']);
                
                // Get driver initials
                $nameParts = explode(' ', $driver['name']);
                $initials = '';
                if (count($nameParts) >= 2) {
                    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1], 0, 1));
                } else {
                    $initials = strtoupper(substr($driver['name'], 0, 2));
                }
                ?>
                
                <a href="driver_conversation.php?driver_id=<?= e($driver['id']) ?>" 
                   class="inbox-item <?= $hasUnread ? 'has-unread' : '' ?>">
                    <div class="driver-avatar"><?= e($initials) ?></div>
                    
                    <div class="driver-info">
                        <div class="driver-name">
                            <?= e($driver['name']) ?>
                            <?php if ($hasUnread): ?>
                                <span class="unread-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="message-preview">
                            <?php if ($lastMessage['sender_type'] === 'officer'): ?>
                                <strong>You:</strong> 
                            <?php endif; ?>
                            <?= e(mb_substr($lastMessage['message_text'], 0, 60)) ?>
                            <?php if (mb_strlen($lastMessage['message_text']) > 60): ?>...<?php endif; ?>
                            <?php if ($lastMessage['attachment_count'] > 0): ?>
                                <span class="attachment-indicator">
                                    📎 <?= $lastMessage['attachment_count'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="message-meta">
                        <span class="message-time"><?= e($messageTime) ?></span>
                        <?php if ($needsReply): ?>
                            <span class="status-badge needs-reply">⚠ Needs Reply</span>
                        <?php elseif ($lastMessage['sender_type'] === 'officer'): ?>
                            <span class="status-badge replied">✓ Replied</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
  </div><!-- /.page -->
</div><!-- /.main-col -->

<?php render_shared_script(); ?>
</body>
</html>
