<?php

require 'driver-database.php';
require_login();
require 'driver-template.php';

// Handle AJAX mark all as read request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    header('Content-Type: application/json');
    try {
        $pdo = get_pdo();
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE driver_id = :id AND is_read = 0')
            ->execute(['id' => $_SESSION['driver_id'] ?? '']);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

render_header([
    'title'          => 'Notifications',
    'topbar_mode'    => 'none',
]);

// Get notification count
$notifications = get_notifications();
$unreadCount = 0;
foreach ($notifications as $n) {
    // Check if is_read key exists and is 0/false
    if (isset($n['is_read']) && !$n['is_read']) {
        $unreadCount++;
    } elseif (!isset($n['is_read'])) {
        // If is_read doesn't exist, treat as unread
        $unreadCount++;
    }
}
?>

<!-- Page Header with Unread Count and Mark All Read -->
<section class="px-4 pt-3 pb-3 bg-white border-b border-slate-100">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2.5">
            <span class="text-base font-bold text-slate-800">Notifications</span>
            <?php if ($unreadCount > 0): ?>
            <span class="bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full border border-amber-200" id="unreadBadge">
                <?php echo $unreadCount; ?> Unread
            </span>
            <?php endif; ?>
        </div>
        <?php if ($unreadCount > 0): ?>
        <button type="button" id="markAllRead" class="text-xs font-semibold text-amber-600 hover:text-amber-700 active:opacity-70 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="mark-text">Mark all read</span>
            <span class="marking-text hidden">Marking...</span>
        </button>
        <?php endif; ?>
    </div>
</section>

<!-- Notifications List -->
<main class="flex-1 overflow-y-auto px-4 py-4 space-y-3.5" data-purpose="notification-cards-stream">
    <?php if (empty($notifications)): ?>
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
            <p class="text-sm font-semibold text-slate-600">No notifications yet</p>
            <p class="text-xs text-slate-400 mt-1">You'll see updates about your applications here</p>
        </div>
    <?php else: ?>
        <?php 
        $currentDate = null;
        foreach ($notifications as $n): 
            $notifDate = date('M j, Y', strtotime($n['date']));
            $isToday = date('Y-m-d', strtotime($n['date'])) === date('Y-m-d');
            $dateLabel = $isToday ? 'Today • ' . $notifDate : $notifDate;
            
            // Show date divider if date changed
            if ($currentDate !== $notifDate):
                $currentDate = $notifDate;
        ?>
        <!-- Date Divider -->
        <div class="flex items-center space-x-2 px-1">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider"><?php echo h($dateLabel); ?></span>
            <div class="h-px bg-slate-200/80 flex-1"></div>
        </div>
        <?php endif; ?>
        
        <!-- Notification Card -->
        <article class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_3px_12px_-2px_rgba(0,0,0,0.05)] relative hover:shadow-md transition-all tap-highlight-transparent active:scale-[0.99] cursor-pointer">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start space-x-3">
                    <!-- Status Dot and Icon -->
                    <div class="mt-1 relative flex-shrink-0">
                        <?php 
                        $isUnread = !isset($n['is_read']) || !$n['is_read'];
                        if ($isUnread): 
                        ?>
                        <span class="w-2.5 h-2.5 bg-amber-500 rounded-full block absolute -top-0.5 -left-0.5 ring-2 ring-white"></span>
                        <?php endif; ?>
                        <div class="w-9 h-9 rounded-xl <?php echo $isUnread ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-400'; ?> flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Card Content -->
                    <div class="flex-1 pr-1">
                        <h2 class="text-sm font-bold text-slate-900 leading-snug"><?php echo h($n['title']); ?></h2>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed"><?php echo h($n['text']); ?></p>
                    </div>
                </div>
                
                <!-- Timestamp -->
                <time class="text-[11px] text-slate-400 whitespace-nowrap font-medium flex-shrink-0">
                    <?php echo h($notifDate); ?>
                </time>
            </div>
        </article>
        <?php endforeach; ?>
        
        <!-- Bottom Spacer -->
        <div class="h-6"></div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const markAllBtn = document.getElementById('markAllRead');
    
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async function() {
            // Disable button and show loading state
            markAllBtn.disabled = true;
            markAllBtn.querySelector('.mark-text').classList.add('hidden');
            markAllBtn.querySelector('.marking-text').classList.remove('hidden');
            
            try {
                // Send AJAX request to mark all as read
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=mark_all_read'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove all unread indicators
                    const unreadDots = document.querySelectorAll('.bg-amber-500.rounded-full');
                    unreadDots.forEach(dot => {
                        dot.remove();
                    });
                    
                    // Update notification card backgrounds
                    const unreadCards = document.querySelectorAll('.bg-amber-50.text-amber-600');
                    unreadCards.forEach(card => {
                        card.classList.remove('bg-amber-50', 'text-amber-600');
                        card.classList.add('bg-slate-50', 'text-slate-400');
                    });
                    
                    // Remove unread badge
                    const unreadBadge = document.getElementById('unreadBadge');
                    if (unreadBadge) {
                        unreadBadge.style.transition = 'opacity 0.3s ease';
                        unreadBadge.style.opacity = '0';
                        setTimeout(() => unreadBadge.remove(), 300);
                    }
                    
                    // Hide the button itself
                    markAllBtn.style.transition = 'opacity 0.3s ease';
                    markAllBtn.style.opacity = '0';
                    setTimeout(() => markAllBtn.remove(), 300);
                    
                } else {
                    // Show error and re-enable button
                    alert('Failed to mark notifications as read. Please try again.');
                    markAllBtn.disabled = false;
                    markAllBtn.querySelector('.mark-text').classList.remove('hidden');
                    markAllBtn.querySelector('.marking-text').classList.add('hidden');
                }
            } catch (error) {
                console.error('Error marking notifications as read:', error);
                alert('An error occurred. Please try again.');
                markAllBtn.disabled = false;
                markAllBtn.querySelector('.mark-text').classList.remove('hidden');
                markAllBtn.querySelector('.marking-text').classList.add('hidden');
            }
        });
    }
});
</script>

<?php render_footer('notif'); ?>