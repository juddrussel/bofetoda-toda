<?php

require 'driver-database.php';
require 'driver-template.php';
require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';

$driver = require_driver();

// Get latest general announcements (for display above renewal section)
$generalAnnouncements = get_latest_announcements(3);

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

<style>
body {
    background: #f5f5f5;
}

.content {
    max-width: 600px;
    margin: 0 auto;
    padding: 16px 16px 100px;
}

/* Header Card */
.header-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-greeting {
    font-size: 12px;
    color: #f59e0b;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.header-name {
    font-size: 24px;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 6px;
    line-height: 1.2;
}

.header-info {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #6b7280;
    flex-wrap: wrap;
}

.header-info-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.header-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    background: #d1fae5;
    color: #065f46;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

/* Section Headers */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding: 0 4px;
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 6px;
}

.section-link {
    font-size: 13px;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Announcement Cards */
.ann-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.ann-card.is-urgent {
    border-left: 3px solid #ef4444;
}

.ann-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
    gap: 12px;
}

.ann-badges {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.ann-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.ann-badge--event {
    background: #3b82f6;
    color: white;
}

.ann-badge--urgent {
    background: #ef4444;
    color: white;
}

.ann-date {
    font-size: 11px;
    color: #9ca3af;
    margin-bottom: 8px;
}

.ann-title {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
    line-height: 1.4;
}

.ann-message {
    font-size: 13px;
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 8px;
}

.ann-message.is-truncated {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.expand-content {
    display: none;
}

.expand-content.is-expanded {
    display: block;
}

.ann-read-more {
    color: #3b82f6;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
}

.ann-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f3f4f6;
}

.ann-meta-row {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 12px;
    color: #6b7280;
}

.ann-meta-icon {
    flex-shrink: 0;
    width: 16px;
    text-align: center;
}

/* Status Card */
.status-card {
    background: white;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.status-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge--progress {
    background: #fef3c7;
    color: #92400e;
}

.status-badge--open {
    background: #d1fae5;
    color: #065f46;
}

.status-badge--upcoming {
    background: #e0e7ff;
    color: #3730a3;
}

.status-badge--approved {
    background: #d1fae5;
    color: #065f46;
}

.status-badge--rejected {
    background: #fee2e2;
    color: #991b1b;
}

.status-subtitle {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 16px;
}

.status-text {
    font-size: 13px;
    color: #4b5563;
    line-height: 1.6;
    margin: 12px 0;
}

.status-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 14px 0;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
}

.status-meta-row {
    display: flex;
    gap: 8px;
    font-size: 13px;
    color: #4b5563;
}

.status-meta-row strong {
    color: #1f2937;
    font-weight: 600;
    min-width: 80px;
}

/* Stepper */
.stepper {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
    position: relative;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.step-line {
    position: absolute;
    top: 18px;
    right: 50%;
    width: 100%;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}

.step.done .step-line {
    background: #fbbf24;
}

.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    z-index: 1;
    position: relative;
    border: 2px solid #e5e7eb;
}

.step.done .step-circle {
    background: #fbbf24;
    color: white;
    border-color: #fbbf24;
}

.step-label {
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    margin-top: 8px;
    text-align: center;
}

.step.done .step-label {
    color: #1f2937;
}

.step-date {
    font-size: 9px;
    color: #9ca3af;
    margin-top: 2px;
    text-align: center;
}

/* Buttons */
.btn {
    display: block;
    width: 100%;
    padding: 14px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    border: none;
    cursor: pointer;
    margin-top: 14px;
    transition: all 0.2s;
}

.btn-primary {
    background: #fbbf24;
    color: #1f2937;
}

.btn-primary:hover {
    background: #f59e0b;
}

.btn-secondary {
    background: white;
    color: #1f2937;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #f9fafb;
}

/* History */
.history-card {
    background: white;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.history-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}

.history-link {
    font-size: 12px;
    color: #6b7280;
    text-decoration: none;
}

.history-empty {
    text-align: center;
    padding: 30px 20px;
    color: #9ca3af;
}

.history-empty-icon {
    font-size: 36px;
    margin-bottom: 8px;
    opacity: 0.3;
}

.history-empty-text {
    font-size: 13px;
    margin-bottom: 4px;
}

.history-empty-subtext {
    font-size: 11px;
    color: #d1d5db;
}

.history-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 8px;
    gap: 10px;
}

.history-item:last-child {
    margin-bottom: 0;
}

.history-year {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
    min-width: 45px;
}

.history-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.history-app {
    font-size: 12px;
    color: #6b7280;
}

.history-date {
    font-size: 11px;
    color: #9ca3af;
}

.history-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.history-badge--approved {
    background: #d1fae5;
    color: #065f46;
}

.history-badge--rejected {
    background: #fee2e2;
    color: #991b1b;
}

.text-muted {
    color: #6b7280;
}

@media (max-width: 640px) {
    .content {
        padding: 12px 12px 100px;
    }
}
</style>

<div class="content">
    
    <!-- Header Card -->
    <div class="header-card">
        <div class="header-greeting">GOOD DAY!</div>
        <div class="header-name"><?= h($driver['name']) ?></div>
        <div class="header-info">
            <div class="header-info-item">
                <span>📋</span> <span><?= h($driver['driver_id']) ?></span>
            </div>
            <div class="header-badge">
                <span>●</span> Active Member
            </div>
        </div>
    </div>

    <!-- Announcements Section -->
    <?php if (!empty($generalAnnouncements)): ?>
        <div class="section-header">
            <h2 class="section-title">📢 Announcements</h2>
            <a href="driver-announcements.php" class="section-link">View All →</a>
        </div>
        
        <?php foreach ($generalAnnouncements as $ann): 
            $truncated = truncate_text($ann['message'], 150);
            $catColor = get_category_color($ann['category']);
            $isUrgent = $ann['priority'] === 'urgent';
        ?>
            <div class="ann-card <?= $isUrgent ? 'is-urgent' : '' ?>" id="ann-<?= (int)$ann['id'] ?>">
                <div class="ann-header">
                    <div style="flex:1;">
                        <div class="ann-date">Oct <?= h(date('j, Y', strtotime($ann['created_at']))) ?></div>
                        <h3 class="ann-title"><?= h($ann['title']) ?></h3>
                    </div>
                    <div class="ann-badges">
                        <?php if ($ann['event_date']): ?>
                            <span class="ann-badge ann-badge--event">EVENT</span>
                        <?php endif; ?>
                        <?php if ($isUrgent): ?>
                            <span class="ann-badge ann-badge--urgent">URGENT</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ann-message <?= $truncated['is_truncated'] ? 'is-truncated' : '' ?>" id="msg-<?= (int)$ann['id'] ?>">
                    <?= nl2br(h($truncated['text'])) ?>
                </div>
                
                <?php if ($truncated['is_truncated']): ?>
                    <div class="expand-content" id="full-<?= (int)$ann['id'] ?>">
                        <?= nl2br(h($ann['message'])) ?>
                    </div>
                    <a href="javascript:void(0)" class="ann-read-more" onclick="toggleMessage(<?= (int)$ann['id'] ?>)" id="toggle-<?= (int)$ann['id'] ?>">
                        Read more...
                    </a>
                <?php endif; ?>
                
                <?php if ($ann['event_date'] || $ann['event_location']): ?>
                <div class="ann-meta">
                    <?php if ($ann['event_date']): ?>
                        <div class="ann-meta-row">
                            <span class="ann-meta-icon">📅</span>
                            <span><strong>Event:</strong> <?= h(format_event_date($ann['event_date'])) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ann['event_location']): ?>
                        <div class="ann-meta-row">
                            <span class="ann-meta-icon">📍</span>
                            <span><strong>Location:</strong> <?= h($ann['event_location']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ann-meta-row">
                        <span class="ann-meta-icon">📝</span>
                        <span><strong>Posted:</strong> <?= h(date('M j, Y', strtotime($ann['created_at']))) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Current Status Section -->
    <?php if ($state === 'open'): ?>
        <div class="section-header" style="margin-top:24px;">
            <h2 class="section-title">Current Status</h2>
        </div>
        <div class="status-card">
            <div class="status-header">
                <div class="status-title">Renewal Period</div>
                <span class="status-badge status-badge--open">OPEN</span>
            </div>
            <p class="status-text"><?= h($openAnnouncement['message']) ?></p>
            <div class="status-meta">
                <div class="status-meta-row"><strong>Date Open:</strong> <span><?= h($openAnnouncement['opened']) ?></span></div>
                <div class="status-meta-row"><strong>Deadline:</strong> <span><?= h($openAnnouncement['deadline']) ?></span></div>
            </div>
            <a href="driver-renewal.php" class="btn btn-primary">Submit Renewal</a>
        </div>

    <?php elseif ($state === 'upcoming'): ?>
        <div class="section-header" style="margin-top:24px;">
            <h2 class="section-title">Current Status</h2>
        </div>
        <div class="status-card">
            <div class="status-header">
                <div class="status-title">Renewal Period</div>
                <span class="status-badge status-badge--upcoming">UPCOMING</span>
            </div>
            <p class="status-text"><?= h($upcomingAnnouncement['message']) ?></p>
            <div class="status-meta">
                <div class="status-meta-row"><strong>Opens:</strong> <span><?= h($upcomingAnnouncement['opened']) ?></span></div>
                <div class="status-meta-row"><strong>Deadline:</strong> <span><?= h($upcomingAnnouncement['deadline']) ?></span></div>
            </div>
            <p class="text-muted" style="font-size:12px;margin-top:12px;">Submission will open on the date above.</p>
        </div>

    <?php elseif ($state === 'submitted'): ?>
        <div class="section-header" style="margin-top:24px;">
            <h2 class="section-title">Current Status</h2>
        </div>
        <div class="status-card">
            <div class="status-header">
                <div>
                    <div class="status-title">Application Status</div>
                    <div class="status-subtitle">Application No. <?= h($currentAppNo) ?></div>
                </div>
                <span class="status-badge status-badge--progress">IN PROGRESS</span>
            </div>

            <div class="stepper">
                <div class="step done">
                    <div class="step-circle">✓</div>
                    <div class="step-label">Submitted</div>
                    <div class="step-date"><?= !empty($currentDetail['submitted_at']) ? h(date('M j', strtotime($currentDetail['submitted_at']))) : '' ?></div>
                </div>
                <div class="step <?= $currentAppStatus !== 'Pending' ? 'done' : '' ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?= $currentAppStatus !== 'Pending' ? '✓' : '2' ?></div>
                    <div class="step-label">Validated</div>
                    <div class="step-date"><?= !empty($currentDetail['validated_at']) ? h(date('M j', strtotime($currentDetail['validated_at']))) : '' ?></div>
                </div>
                <div class="step <?= in_array($currentAppStatus, ['Approved'], true) ? 'done' : '' ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?= $currentAppStatus === 'Approved' ? '✓' : '3' ?></div>
                    <div class="step-label">Approved</div>
                    <div class="step-date"><?= (!empty($currentDetail['approved_at']) && $currentAppStatus === 'Approved') ? h(date('M j', strtotime($currentDetail['approved_at']))) : '' ?></div>
                </div>
                <div class="step">
                    <div class="step-line"></div>
                    <div class="step-circle">4</div>
                    <div class="step-label">Done</div>
                    <div class="step-date"></div>
                </div>
            </div>

            <a href="driver-view-application.php" class="btn btn-primary">View Details</a>
        </div>

    <?php elseif ($state === 'rejected'): ?>
        <div class="section-header" style="margin-top:24px;">
            <h2 class="section-title">Current Status</h2>
        </div>
        <div class="status-card">
            <div class="status-header">
                <div class="status-title">Application Status</div>
                <span class="status-badge status-badge--rejected">REJECTED</span>
            </div>
            <div style="background:#fef2f2;border-left:3px solid #ef4444;padding:12px;border-radius:6px;margin:12px 0;">
                <p style="font-weight:700;font-size:14px;margin:0 0 4px 0;color:#991b1b;"><?= h($currentDetail['reject_reason'] ?? 'Application Rejected') ?></p>
                <p style="font-size:12px;color:#6b7280;margin:0;"><?= h($currentDetail['reject_note'] ?: 'Please review and resubmit.') ?></p>
            </div>
            <?php if ($openAnnouncement): ?>
                <div class="status-meta">
                    <div class="status-meta-row"><strong>Date Open:</strong> <span><?= h($openAnnouncement['opened']) ?></span></div>
                    <div class="status-meta-row"><strong>Deadline:</strong> <span><?= h($openAnnouncement['deadline']) ?></span></div>
                </div>
            <?php endif; ?>
            <a href="driver-renewal.php?start=1" class="btn btn-primary">Re-Apply Now</a>
        </div>

    <?php elseif ($state === 'approved'): ?>
        <div class="section-header" style="margin-top:24px;">
            <h2 class="section-title">Current Status</h2>
        </div>
        <div class="status-card">
            <div class="status-header">
                <div>
                    <div class="status-title">Application Status</div>
                    <div class="status-subtitle">Application No. <?= h($currentAppNo) ?></div>
                </div>
                <span class="status-badge status-badge--approved">APPROVED</span>
            </div>
            <p class="status-text">Your renewal for this cycle has been approved. There's nothing else to submit right now.</p>
            <a href="driver-view-application.php" class="btn btn-secondary">View Details</a>
        </div>
    <?php endif; ?>

    <!-- Renewal History -->
    <div class="section-header" style="margin-top:24px;">
        <h2 class="section-title">Renewal History</h2>
        <a href="#" class="history-link">Archive</a>
    </div>
    <div class="history-card">
        <?php $history = get_renewal_history($driver['driver_id']); ?>
        <?php if (!$history): ?>
            <div class="history-empty">
                <div class="history-empty-icon">📋</div>
                <div class="history-empty-text">No renewal history yet.</div>
                <div class="history-empty-subtext">Past records and approved permits will show here once available.</div>
            </div>
        <?php else: ?>
            <?php foreach ($history as $item): ?>
            <div class="history-item">
                <span class="history-year"><?= h($item['year']) ?></span>
                <div class="history-info">
                    <span class="history-app"><?= h($item['app_no']) ?></span>
                    <span class="history-date"><?= h($item['date']) ?></span>
                </div>
                <span class="history-badge history-badge--<?= $item['status'] === 'APPROVED' ? 'approved' : 'rejected' ?>"><?= h($item['status']) ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
function toggleMessage(annId) {
    const truncatedMsg = document.getElementById('msg-' + annId);
    const fullMsg = document.getElementById('full-' + annId);
    const toggleBtn = document.getElementById('toggle-' + annId);
    
    if (fullMsg.classList.contains('is-expanded')) {
        // Collapse
        truncatedMsg.style.display = 'block';
        fullMsg.classList.remove('is-expanded');
        toggleBtn.textContent = 'Read more...';
    } else {
        // Expand
        truncatedMsg.style.display = 'none';
        fullMsg.classList.add('is-expanded');
        toggleBtn.textContent = 'Show less';
    }
}
</script>
    
<?php render_footer('home'); ?>