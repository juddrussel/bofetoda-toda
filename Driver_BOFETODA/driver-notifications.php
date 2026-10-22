<?php

require 'driver-database.php';
require_login();
require 'driver-template.php';


try {
    $pdo = get_pdo();
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE driver_id = :id AND is_read = 0')
        ->execute(['id' => $_SESSION['driver_id'] ?? '']);
} catch (Throwable $e) {
   
}

render_header([
    'title'          => 'Notifications',
    'topbar_mode'    => 'simple',
    'topbar_heading' => 'Notifications',
]);
?>

<div class="content">
    <?php foreach (get_notifications() as $n): ?>
    <div class="notif-card">
        <span class="notif-dot"></span>
        <div>
            <div class="notif-head">
                <div class="notif-title"><?php echo h($n['title']); ?></div>
                <?php if (!empty($n['date'])): ?><div class="notif-date"><?php echo h($n['date']); ?></div><?php endif; ?>
            </div>
            <div class="notif-text"><?php echo h($n['text']); ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php render_footer('notif'); ?>