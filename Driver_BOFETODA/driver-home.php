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
.content {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px 16px 100px;
}

/* Welcome Section */
.welcome-section {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    border-radius: 16px;
    padding: 28px 24px;
    margin-bottom: 24px;
    color: white;
    box-shadow: 0 4px 20px rgba(30, 58, 138, 0.15);
}

.welcome-greeting {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 6px;
    font-weight: 500;
}

.welcome-name {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
    line-height: 1.2;
}

.welcome-subtitle {
    font-size: 14px;
    opacity: 0.85;
    line-height: 1.5;
}

/* Announcements Section */
.section {
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.section-title {
    font-size: 20px;
    font-weight: 800;
    color: var(--navy);
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-link {
    font-size: 14px;
    color: var(--accent);
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.section-link:hover {
    text-decoration: underline;
}

/* Announcement Cards */
.ann-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}

.ann-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.ann-card.is-urgent {
    border-left: 4px solid #dc2626;
    background: linear-gradient(to right, #fef2f2 0%, white 20%);
}

.ann-badges {
    display: flex;
    gap: 6px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.ann-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ann-badge--urgent {
    background: #dc2626;
    color: white;
}

.ann-badge--category {
    background: var(--cat-color);
    color: white;
}

.ann-title {
    font-size: 17px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 10px;
    line-height: 1.4;
}

.ann-message {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.7;
    margin-bottom: 0;
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
    color: var(--accent);
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    display: inline-block;
    margin-top: 8px;
}

.ann-read-more:hover {
    text-decoration: underline;
}

.ann-images {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 8px;
    margin-top: 14px;
}

.ann-images img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    cursor: pointer;
    transition: transform 0.2s;
}

.ann-images img:hover {
    transform: scale(1.05);
}

.ann-images-more {
    width: 100%;
    height: 80px;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    color: #6b7280;
    background: #f9fafb;
}

.ann-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #e5e7eb;
}

.ann-meta-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #6b7280;
}

.ann-meta-row strong {
    color: var(--navy);
    font-weight: 600;
}

.no-announcements {
    text-align: center;
    padding: 40px 20px;
    background: white;
    border-radius: 12px;
    color: #9ca3af;
    font-size: 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.no-announcements-icon {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}

/* Status Cards */
.status-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    position: relative;
}

.status-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge--open {
    background: #10b981;
    color: white;
}

.status-badge--upcoming {
    background: #f59e0b;
    color: white;
}

.status-badge--approved {
    background: #10b981;
    color: white;
}

.status-badge--rejected {
    background: #ef4444;
    color: white;
}

.status-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 8px;
    padding-right: 120px;
}

.status-subtitle {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 16px;
}

.status-text {
    font-size: 14px;
    color: #4b5563;
    line-height: 1.7;
    margin: 14px 0;
}

.status-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 16px 0;
}

.status-meta-row {
    display: flex;
    gap: 6px;
    font-size: 14px;
    color: #4b5563;
}

.status-meta-row strong {
    color: var(--navy);
    font-weight: 600;
    min-width: 90px;
}

.btn {
    display: inline-block;
    padding: 14px 28px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    margin-top: 16px;
}

.btn-primary {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
    background: white;
    color: var(--navy);
    border: 2px solid var(--navy);
}

.btn-secondary:hover {
    background: var(--navy);
    color: white;
}

/* Stepper */
.stepper {
    display: flex;
    justify-content: space-between;
    margin: 28px 0;
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
    top: 16px;
    right: 50%;
    width: 100%;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}

.step.done .step-line {
    background: #10b981;
}

.step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    z-index: 1;
    position: relative;
}

.step.done .step-circle {
    background: #10b981;
    color: white;
}

.step-label {
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    margin-top: 8px;
    text-align: center;
}

.step.done .step-label {
    color: var(--navy);
}

.step-date {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 4px;
}

/* History */
.history-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 8px;
    gap: 12px;
}

.history-year {
    font-size: 15px;
    font-weight: 700;
    color: var(--navy);
    min-width: 50px;
}

.history-app {
    font-size: 13px;
    color: #6b7280;
    flex: 1;
}

.history-date {
    font-size: 12px;
    color: #9ca3af;
}

.history-badge {
    padding: 4px 10px;
    border-radius: 6px;
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
        padding: 16px 12px 100px;
    }
    
    .welcome-section {
        padding: 24px 20px;
    }
    
    .welcome-name {
        font-size: 24px;
    }
    
    .status-title {
        font-size: 19px;
        padding-right: 100px;
    }
    
    .status-badge {
        top: 16px;
        right: 16px;
        padding: 5px 10px;
        font-size: 10px;
    }
    
    .stepper {
        margin: 20px -10px;
    }
    
    .step-circle {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
    
    .history-item {
        flex-wrap: wrap;
    }
}
</style>

<div class="content">
    
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-greeting">Good Day!</div>
        <div class="welcome-name"><?= h($driver['name']) ?></div>
        <div class="welcome-subtitle">Welcome to your BOFETODA driver portal</div>
    </div>

    <!-- General Announcements Section -->
    <?php if (!empty($generalAnnouncements)): ?>
        <div class="section">
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
                    <div class="ann-badges">
                        <?php if ($isUrgent): ?>
                            <span class="ann-badge ann-badge--urgent">🔥 URGENT</span>
                        <?php endif; ?>
                        <span class="ann-badge ann-badge--category" style="--cat-color: <?= h($catColor) ?>; background: <?= h($catColor) ?>;">
                            <?= h($ann['category']) ?>
                        </span>
                    </div>
                    
                    <h3 class="ann-title"><?= h($ann['title']) ?></h3>
                    
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
                    
                    <?php if ($ann['image_count'] > 0): 
                        $images = get_announcement_images($ann['id']);
                        if (!empty($images)):
                    ?>
                        <div class="ann-images">
                            <?php foreach (array_slice($images, 0, 4) as $img): ?>
                                <img src="<?= h(get_announcement_image_url($img['image_path'])) ?>" alt="Announcement" loading="lazy">
                            <?php endforeach; ?>
                            <?php if (count($images) > 4): ?>
                                <div class="ann-images-more">+<?= count($images) - 4 ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; endif; ?>
                    
                    <?php if ($ann['event_date'] || $ann['event_location']): ?>
                    <div class="ann-meta">
                        <?php if ($ann['event_date']): ?>
                            <div class="ann-meta-row">
                                <strong>📅 Event:</strong> <span><?= h(format_event_date($ann['event_date'])) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($ann['event_location']): ?>
                            <div class="ann-meta-row">
                                <strong>📍 Location:</strong> <span><?= h($ann['event_location']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Renewal Status Section -->
    <?php if ($state === 'open'): ?>
        <div class="section">
            <h2 class="section-title">🔄 Renewal Status</h2>
            <div class="status-card">
                <span class="status-badge status-badge--open">OPEN</span>
                <div class="status-title"><?= h($openAnnouncement['title']) ?></div>
                <p class="status-text"><?= h($openAnnouncement['message']) ?></p>
                <div class="status-meta">
                    <div class="status-meta-row"><strong>Date Open:</strong> <span><?= h($openAnnouncement['opened']) ?></span></div>
                    <div class="status-meta-row"><strong>Deadline:</strong> <span><?= h($openAnnouncement['deadline']) ?></span></div>
                </div>
                <a href="driver-renewal.php" class="btn btn-primary">Submit Renewal Application</a>
            </div>
        </div>

    <?php elseif ($state === 'upcoming'): ?>
        <div class="section">
            <h2 class="section-title">🔄 Renewal Status</h2>
            <div class="status-card">
                <span class="status-badge status-badge--upcoming">UPCOMING</span>
                <div class="status-title"><?= h($upcomingAnnouncement['title']) ?></div>
                <p class="status-text"><?= h($upcomingAnnouncement['message']) ?></p>
                <div class="status-meta">
                    <div class="status-meta-row"><strong>Opens:</strong> <span><?= h($upcomingAnnouncement['opened']) ?></span></div>
                    <div class="status-meta-row"><strong>Deadline:</strong> <span><?= h($upcomingAnnouncement['deadline']) ?></span></div>
                </div>
                <p class="text-muted" style="font-size:13px;margin-top:16px;">Submission will open on the date above. Come back then to apply.</p>
            </div>
        </div>

    <?php elseif ($state === 'submitted'): ?>
        <div class="section">
            <h2 class="section-title">🔄 Renewal Status</h2>
            <div class="status-card">
                <div class="status-title">Application in Progress</div>
                <div class="status-subtitle">Application No. <?= h($currentAppNo) ?></div>

                <div class="stepper">
                    <div class="step done">
                        <div class="step-circle">✓</div>
                        <div class="step-label">Submitted</div>
                        <div class="step-date"><?= !empty($currentDetail['submitted_at']) ? h(date('M j, Y', strtotime($currentDetail['submitted_at']))) : '' ?></div>
                    </div>
                    <div class="step <?= $currentAppStatus !== 'Pending' ? 'done' : '' ?>">
                        <div class="step-line"></div>
                        <div class="step-circle"><?= $currentAppStatus !== 'Pending' ? '✓' : '2' ?></div>
                        <div class="step-label">Validated</div>
                        <div class="step-date"><?= !empty($currentDetail['validated_at']) ? h(date('M j, Y', strtotime($currentDetail['validated_at']))) : '' ?></div>
                    </div>
                    <div class="step <?= in_array($currentAppStatus, ['Approved'], true) ? 'done' : '' ?>">
                        <div class="step-line"></div>
                        <div class="step-circle"><?= $currentAppStatus === 'Approved' ? '✓' : '3' ?></div>
                        <div class="step-label">Approved</div>
                        <div class="step-date"><?= (!empty($currentDetail['approved_at']) && $currentAppStatus === 'Approved') ? h(date('M j, Y', strtotime($currentDetail['approved_at']))) : '' ?></div>
                    </div>
                    <div class="step">
                        <div class="step-line"></div>
                        <div class="step-circle">4</div>
                        <div class="step-label">Complete</div>
                        <div class="step-date"></div>
                    </div>
                </div>

                <a href="driver-view-application.php" class="btn btn-secondary">View Application Details</a>
            </div>
        </div>

    <?php elseif ($state === 'rejected'): ?>
        <div class="section">
            <h2 class="section-title">🔄 Renewal Status</h2>
            <div class="status-card">
                <span class="status-badge status-badge--rejected">REJECTED</span>
                <?php if ($openAnnouncement): ?>
                    <div class="status-title"><?= h($openAnnouncement['title']) ?></div>
                    <p class="status-text"><?= h($openAnnouncement['message']) ?></p>
                    <div class="status-meta">
                        <div class="status-meta-row"><strong>Date Open:</strong> <span><?= h($openAnnouncement['opened']) ?></span></div>
                        <div class="status-meta-row"><strong>Deadline:</strong> <span><?= h($openAnnouncement['deadline']) ?></span></div>
                    </div>
                <?php else: ?>
                    <div class="status-title">Application Rejected</div>
                <?php endif; ?>
                <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:14px 16px;border-radius:8px;margin:16px 0;">
                    <p style="font-weight:700;font-size:15px;margin:0 0 6px 0;color:#991b1b;"><?= h($currentDetail['reject_reason'] ?? 'Application Rejected') ?></p>
                    <p style="font-size:13px;color:#6b7280;margin:0;"><?= h($currentDetail['reject_note'] ?: 'Please review and resubmit your application.') ?></p>
                </div>
                <a href="driver-renewal.php?start=1" class="btn btn-primary">Re-Apply Now</a>
            </div>
        </div>

    <?php elseif ($state === 'approved'): ?>
        <div class="section">
            <h2 class="section-title">🔄 Renewal Status</h2>
            <div class="status-card">
                <span class="status-badge status-badge--approved">APPROVED</span>
                <div class="status-title">Renewal Approved</div>
                <div class="status-subtitle">Application No. <?= h($currentAppNo) ?></div>
                <p class="status-text">Your renewal for this cycle has been approved. There's nothing else to submit right now.</p>
                <a href="driver-view-application.php" class="btn btn-secondary">View Application Details</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Renewal History -->
    <div class="section">
        <h2 class="section-title">📜 Renewal History</h2>
        <div class="status-card">
            <?php $history = get_renewal_history($driver['driver_id']); ?>
            <?php if (!$history): ?>
                <p class="text-muted" style="font-size:14px;">No renewal history yet.</p>
            <?php else: ?>
                <?php foreach ($history as $item): ?>
                <div class="history-item">
                    <span class="history-year"><?= h($item['year']) ?></span>
                    <span class="history-app"><?= h($item['app_no']) ?></span>
                    <span class="history-date"><?= h($item['date']) ?></span>
                    <span class="history-badge history-badge--<?= $item['status'] === 'APPROVED' ? 'approved' : 'rejected' ?>"><?= h($item['status']) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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