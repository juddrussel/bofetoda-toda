<?php

require 'driver-database.php';
require 'driver-template.php';

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
.gen-ann-section {
    margin-bottom: 24px;
}

.gen-ann-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.gen-ann-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--navy);
}

.gen-ann-link {
    font-size: 14px;
    color: var(--accent);
    text-decoration: none;
    font-weight: 600;
}

.gen-ann-link:hover {
    text-decoration: underline;
}

.gen-ann-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 20px;
    margin-bottom: 14px;
    box-shadow: var(--shadow-card);
    position: relative;
}

.gen-ann-card.is-urgent {
    border-left: 4px solid #dc2626;
    background: #fef2f2;
}

.gen-ann-badges {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.gen-ann-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
}

.gen-ann-badge--urgent {
    background: #dc2626;
}

.gen-ann-badge--category {
    background: var(--cat-color);
}

.gen-ann-card__title {
    font-size: 16px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 8px;
    line-height: 1.3;
}

.gen-ann-card__message {
    font-size: 14px;
    color: var(--text);
    line-height: 1.6;
    margin-bottom: 10px;
}

.gen-ann-card__message.is-truncated {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.gen-ann-read-more {
    color: var(--accent);
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin-top: 4px;
}

.gen-ann-read-more:hover {
    text-decoration: underline;
}

.gen-ann-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 13px;
    color: var(--text-muted);
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
}

.gen-ann-meta-row {
    display: flex;
    gap: 4px;
}

.gen-ann-meta strong {
    color: var(--navy);
    font-weight: 600;
}

.gen-ann-image {
    width: 100%;
    max-width: 200px;
    height: auto;
    border-radius: 6px;
    margin-top: 12px;
    border: 1px solid var(--border);
}

.gen-ann-images {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.gen-ann-images img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--border);
    cursor: pointer;
    transition: transform 0.2s ease;
}

.gen-ann-images img:hover {
    transform: scale(1.05);
}

.no-announcements {
    text-align: center;
    padding: 30px 20px;
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text-muted);
    font-size: 14px;
}

.expand-content {
    display: none;
}

.expand-content.is-expanded {
    display: block;
}
</style>

<div class="content">

    <!-- General Announcements Section -->
    <?php if (!empty($generalAnnouncements)): ?>
        <div class="gen-ann-section">
            <div class="gen-ann-header">
                <h2 class="gen-ann-title">📢 Announcements</h2>
                <a href="driver-announcements.php" class="gen-ann-link">View All →</a>
            </div>
            
            <?php foreach ($generalAnnouncements as $ann): 
                $truncated = truncate_text($ann['message'], 150);
                $catColor = get_category_color($ann['category']);
                $isUrgent = $ann['priority'] === 'urgent';
            ?>
                <div class="gen-ann-card <?= $isUrgent ? 'is-urgent' : '' ?>" id="ann-<?= (int)$ann['id'] ?>">
                    <div class="gen-ann-badges">
                        <?php if ($isUrgent): ?>
                            <span class="gen-ann-badge gen-ann-badge--urgent">🔥 URGENT</span>
                        <?php endif; ?>
                        <span class="gen-ann-badge gen-ann-badge--category" style="--cat-color: <?= h($catColor) ?>; background: <?= h($catColor) ?>;">
                            <?= h($ann['category']) ?>
                        </span>
                    </div>
                    
                    <h3 class="gen-ann-card__title"><?= h($ann['title']) ?></h3>
                    
                    <div class="gen-ann-card__message <?= $truncated['is_truncated'] ? 'is-truncated' : '' ?>" id="msg-<?= (int)$ann['id'] ?>">
                        <?= nl2br(h($truncated['text'])) ?>
                    </div>
                    
                    <?php if ($truncated['is_truncated']): ?>
                        <span class="expand-content" id="full-<?= (int)$ann['id'] ?>">
                            <?= nl2br(h($ann['message'])) ?>
                        </span>
                        <a href="javascript:void(0)" class="gen-ann-read-more" onclick="toggleMessage(<?= (int)$ann['id'] ?>)" id="toggle-<?= (int)$ann['id'] ?>">
                            Read more...
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($ann['image_count'] > 0): 
                        $images = get_announcement_images($ann['id']);
                        if (!empty($images)):
                    ?>
                        <div class="gen-ann-images">
                            <?php foreach (array_slice($images, 0, 4) as $img): ?>
                                <img src="<?= h(get_announcement_image_url($img['image_path'])) ?>" alt="Announcement image" loading="lazy">
                            <?php endforeach; ?>
                            <?php if (count($images) > 4): ?>
                                <div style="width:80px;height:80px;border:1px solid var(--border);border-radius:6px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;font-size:14px;font-weight:700;color:var(--text-muted);">
                                    +<?= count($images) - 4 ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; endif; ?>
                    
                    <div class="gen-ann-meta">
                        <?php if ($ann['event_date']): ?>
                            <div class="gen-ann-meta-row">
                                <strong>📅 Event:</strong> <?= h(format_event_date($ann['event_date'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($ann['event_location']): ?>
                            <div class="gen-ann-meta-row">
                                <strong>📍 Location:</strong> <?= h($ann['event_location']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="gen-ann-meta-row">
                            <strong>Posted:</strong> <?= h(date('M j, Y', strtotime($ann['created_at']))) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Renewal Status Section (existing code) -->

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