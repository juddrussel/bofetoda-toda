<?php

require 'driver-database.php';
require 'driver-template.php';

$driver = require_driver();


$currentAppNo = get_current_application($driver['driver_id']);
$currentAppStatus = null;
$currentDetail = null;
if ($currentAppNo) {
    $currentDetail = get_application_detail($currentAppNo);
    $currentAppStatus = $currentDetail['status'] ?? null;
}

if ($currentAppStatus
    && in_array($currentAppStatus, ['Approved', 'Rejected'], true)
    && !application_is_current_cycle($currentDetail)
) {
    $currentAppNo = null;
    $currentAppStatus = null;
    $currentDetail = null;
}

$openAnnouncement = null;
foreach (get_open_announcement() ?? [] as $a) {
    $openAnnouncement = $a;
    break;
}


$upcomingAnnouncement = null;
if (!$openAnnouncement) {
    foreach (get_upcoming_announcement() ?? [] as $a) {
        $upcomingAnnouncement = $a;
        break;
    }
}

if ($currentAppStatus && !in_array($currentAppStatus, ['Approved', 'Rejected'], true)) {
    $state = 'submitted';
} elseif ($currentAppStatus === 'Rejected') {
    $state = 'rejected';
} elseif ($currentAppStatus === 'Approved') {
    $state = 'approved';
} elseif ($openAnnouncement) {
    $state = 'open';
} elseif ($upcomingAnnouncement) {
    $state = 'upcoming';
} else {
    $state = 'none';
}

render_header([
    'title'          => 'Home',
    'topbar_mode'    => 'home',
    'topbar_heading' => $driver['name'],
]);
?>

<div class="content">

    <?php if ($state === 'open'): ?>
        <div class="card" style="position:relative;">
            <span class="badge badge--open status-tag">OPEN</span>
            <div class="announcement-title"><?php echo h($openAnnouncement['title']); ?></div>
            <p class="announcement-text"><?php echo h($openAnnouncement['message']); ?></p>
            <div class="announcement-meta"><strong>Date Open:</strong> <span><?php echo h($openAnnouncement['opened']); ?></span></div>
            <div class="announcement-meta"><strong>Deadline:</strong> <span><?php echo h($openAnnouncement['deadline']); ?></span></div>
            <a href="driver-renewal.php" class="card-cta">Submit now!</a>
        </div>

    <?php elseif ($state === 'upcoming'): ?>
        <div class="card" style="position:relative;">
            <span class="badge badge--pending status-tag">UPCOMING</span>
            <div class="announcement-title"><?php echo h($upcomingAnnouncement['title']); ?></div>
            <p class="announcement-text"><?php echo h($upcomingAnnouncement['message']); ?></p>
            <div class="announcement-meta"><strong>Opens:</strong> <span><?php echo h($upcomingAnnouncement['opened']); ?></span></div>
            <div class="announcement-meta"><strong>Deadline:</strong> <span><?php echo h($upcomingAnnouncement['deadline']); ?></span></div>
            <p class="text-muted" style="font-size:12.5px;margin-top:10px;">Submission will open on the date above. Come back then to apply.</p>
        </div>

    <?php elseif ($state === 'submitted'): ?>
        <div class="card">
            <div class="card-title" style="margin-bottom:4px;">Current Status</div>
            <div class="text-muted" style="font-size:13px;margin-bottom:18px;">Application No. <?php echo h($currentAppNo); ?></div>

            <div class="stepper">
                <div class="step done">
                    <div class="step-circle">&#10003;</div>
                    <div class="step-label" style="font-size:10px;">Submitted</div>
                    <div class="step-date"><?php echo !empty($currentDetail['submitted_at']) ? h(date('M j, Y', strtotime($currentDetail['submitted_at']))) : ''; ?></div>
                </div>
                <div class="step <?php echo $currentAppStatus !== 'Pending' ? 'done' : ''; ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?php echo $currentAppStatus !== 'Pending' ? '&#10003;' : '2'; ?></div>
                    <div class="step-label" style="font-size:10px;">Validated</div>
                    <div class="step-date"><?php echo !empty($currentDetail['validated_at']) ? h(date('M j, Y', strtotime($currentDetail['validated_at']))) : ''; ?></div>
                </div>
                <div class="step <?php echo in_array($currentAppStatus, ['Approved'], true) ? 'done' : ''; ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?php echo $currentAppStatus === 'Approved' ? '&#10003;' : '3'; ?></div>
                    <div class="step-label" style="font-size:10px;">Approved</div>
                    <div class="step-date"><?php echo (!empty($currentDetail['approved_at']) && $currentAppStatus === 'Approved') ? h(date('M j, Y', strtotime($currentDetail['approved_at']))) : ''; ?></div>
                </div>
                <div class="step">
                    <div class="step-line"></div>
                    <div class="step-circle">4</div>
                    <div class="step-label" style="font-size:10px;">Done</div>
                    <div class="step-date"><?php echo (!empty($currentDetail['approved_at']) && $currentAppStatus === 'Approved') ? h(date('M j, Y', strtotime($currentDetail['approved_at']))) : ''; ?></div>
                </div>
            </div>

            <a href="driver-view-application.php" class="btn btn-accent">View Details</a>
        </div>

    <?php elseif ($state === 'rejected'): ?>
        <div class="card" style="position:relative;">
            <span class="badge badge--rejected status-tag">REJECTED</span>
            <?php if ($openAnnouncement): ?>
                <div class="announcement-title"><?php echo h($openAnnouncement['title']); ?></div>
                <p class="announcement-text"><?php echo h($openAnnouncement['message']); ?></p>
                <div class="announcement-meta"><strong>Date Open:</strong> <span><?php echo h($openAnnouncement['opened']); ?></span></div>
                <div class="announcement-meta"><strong>Deadline:</strong> <span><?php echo h($openAnnouncement['deadline']); ?></span></div>
            <?php else: ?>
                <div class="card-title" style="margin-bottom:2px;">Admin Remarks</div>
            <?php endif; ?>
            <p style="font-weight:700;font-size:14px;margin-top:16px;margin-bottom:8px;"><?php echo h($currentDetail['reject_reason'] ?? 'Application Rejected'); ?></p>
            <p class="text-muted" style="font-size:13px;"><?php echo h($currentDetail['reject_note'] ?: 'Please review and resubmit your application.'); ?></p>
            <a href="driver-renewal.php?start=1" class="card-cta">Re-Apply</a>
        </div>

    <?php elseif ($state === 'approved'): ?>
        <div class="card" style="position:relative;">
            <span class="badge badge--approved status-tag">APPROVED</span>
            <div class="card-title" style="margin-bottom:4px;">Current Status</div>
            <div class="text-muted" style="font-size:13px;">Application No. <?php echo h($currentAppNo); ?></div>
            <p class="text-muted" style="font-size:13px;margin-top:12px;">Your renewal for this cycle has been approved. There&rsquo;s nothing else to submit right now.</p>
            <a href="driver-view-application.php" class="btn btn-accent" style="margin-top:14px;">View Details</a>
        </div>

    <?php endif; ?>

    <div class="card">
        <div class="card-title">Renewal History</div>
        <?php $history = get_renewal_history($driver['driver_id']); ?>
        <?php if (!$history): ?>
            <p class="text-muted" style="font-size:13px;">No renewal history yet.</p>
        <?php endif; ?>
        <?php foreach ($history as $item): ?>
        <div class="history-item">
            <span class="history-year"><?php echo h($item['year']); ?></span>
            <span class="history-app"><?php echo h($item['app_no']); ?></span>
            <span class="history-date"><?php echo h($item['date']); ?></span>
            <span class="badge <?php echo $item['status'] === 'APPROVED' ? 'badge--approved' : 'badge--rejected'; ?>"><?php echo h($item['status']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>

</div>
    
<?php render_footer('home'); ?>