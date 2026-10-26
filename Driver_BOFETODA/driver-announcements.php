<?php

require 'driver-database.php';
require 'driver-template.php';
require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';

$driver = require_driver();

// Get filter and search parameters
$category = $_GET['category'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Build filters array
$filters = [];
if ($category !== '') {
    $filters['category'] = $category;
}
if ($search !== '') {
    $filters['search'] = $search;
}

// Get paginated announcements
$result = get_paginated_announcements($page, $perPage, $filters);
$announcements = $result['items'];
$totalPages = $result['total_pages'];
$total = $result['total'];

render_header([
    'title' => 'Announcements',
    'topbar_mode' => 'normal',
    'topbar_heading' => 'All Announcements',
]);
?>

<style>
.announcements-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
    flex-wrap: wrap;
}

.announcements-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--navy);
}

.back-link {
    color: var(--accent);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.back-link:hover {
    text-decoration: underline;
}

.filters-bar {
    background: white;
    padding: 16px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    margin-bottom: 20px;
    box-shadow: var(--shadow-card);
}

.filters-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-input,
.filter-select {
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.filter-input {
    flex: 1;
    min-width: 250px;
}

.filter-select {
    min-width: 150px;
}

.filter-btn {
    padding: 10px 20px;
    background: var(--accent);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
}

.filter-btn:hover {
    background: var(--accent-dark, #0056b3);
}

.clear-btn {
    padding: 10px 20px;
    background: white;
    color: var(--navy);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.clear-btn:hover {
    background: #f3f4f6;
}

.results-info {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 16px;
}

.announcement-full {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-card);
}

.announcement-full.is-urgent {
    border-left: 4px solid #dc2626;
    background: #fef2f2;
}

.ann-full-badges {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.ann-full-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
}

.ann-full-badge--urgent {
    background: #dc2626;
}

.ann-full-badge--category {
    background: var(--cat-color);
}

.ann-full-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 12px;
    line-height: 1.3;
}

.ann-full-message {
    font-size: 15px;
    color: var(--text);
    line-height: 1.7;
    margin-bottom: 16px;
    white-space: pre-wrap;
}

.ann-full-images {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
    margin: 20px 0;
}

.ann-full-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border);
    cursor: pointer;
    transition: transform 0.2s ease;
}

.ann-full-image:hover {
    transform: scale(1.03);
}

.ann-full-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
    font-size: 14px;
    color: var(--text-muted);
}

.ann-full-meta-row {
    display: flex;
    gap: 6px;
    align-items: flex-start;
}

.ann-full-meta strong {
    color: var(--navy);
    font-weight: 600;
    min-width: 80px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
    padding: 20px 0;
}

.pagination-btn {
    padding: 8px 16px;
    background: white;
    color: var(--navy);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.pagination-btn:hover:not(.is-disabled) {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.pagination-btn.is-active {
    background: var(--accent);
    color: white;
    border-color: var(--accent);
}

.pagination-btn.is-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-info {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0 12px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
}

.empty-state__icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state h3 {
    color: var(--navy);
    margin-bottom: 8px;
}

.empty-state p {
    color: var(--text-muted);
    margin-bottom: 20px;
}

/* Lightbox */
.lightbox {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.lightbox.is-open {
    display: flex;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
}

.lightbox-image {
    max-width: 100%;
    max-height: 90vh;
    object-fit: contain;
    border-radius: 8px;
}

.lightbox-close {
    position: absolute;
    top: -40px;
    right: 0;
    background: white;
    color: black;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.lightbox-nav:hover {
    background: white;
}

.lightbox-prev {
    left: 20px;
}

.lightbox-next {
    right: 20px;
}

.lightbox-counter {
    position: absolute;
    bottom: -40px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    font-size: 14px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .filters-row {
        flex-direction: column;
    }
    
    .filter-input {
        min-width: 100%;
    }
    
    .ann-full-images {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
    
    .ann-full-image {
        height: 120px;
    }
}
</style>

<div class="content">
    <div class="announcements-header">
        <h1 class="announcements-title">📢 All Announcements</h1>
        <a href="driver-home.php" class="back-link">← Back to Home</a>
    </div>
    
    <!-- Filters -->
    <form class="filters-bar" method="GET" action="">
        <div class="filters-row">
            <input type="text" name="search" class="filter-input" placeholder="Search announcements..." value="<?= h($search) ?>">
            
            <select name="category" class="filter-select">
                <option value="">All Categories</option>
                <option value="Event" <?= $category === 'Event' ? 'selected' : '' ?>>Event</option>
                <option value="Reminder" <?= $category === 'Reminder' ? 'selected' : '' ?>>Reminder</option>
                <option value="Update" <?= $category === 'Update' ? 'selected' : '' ?>>Update</option>
                <option value="News" <?= $category === 'News' ? 'selected' : '' ?>>News</option>
                <option value="Other" <?= $category === 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
            
            <button type="submit" class="filter-btn">Search</button>
            <a href="driver-announcements.php" class="clear-btn">Clear</a>
        </div>
    </form>
    
    <?php if ($total > 0): ?>
        <div class="results-info">
            Showing <?= count($announcements) ?> of <?= $total ?> announcement<?= $total !== 1 ? 's' : '' ?>
            <?php if ($category): ?>
                in <strong><?= h($category) ?></strong>
            <?php endif; ?>
            <?php if ($search): ?>
                matching "<strong><?= h($search) ?></strong>"
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Announcements List -->
    <?php if (empty($announcements)): ?>
        <div class="empty-state">
            <div class="empty-state__icon">🔍</div>
            <h3>No Announcements Found</h3>
            <p>
                <?php if ($search || $category): ?>
                    Try adjusting your search or filter criteria.
                <?php else: ?>
                    There are no announcements at this time.
                <?php endif; ?>
            </p>
            <?php if ($search || $category): ?>
                <a href="driver-announcements.php" class="btn btn-accent">View All Announcements</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $ann): 
            $catColor = get_category_color($ann['category']);
            $isUrgent = $ann['priority'] === 'urgent';
            $images = get_announcement_images($ann['id']);
        ?>
            <div class="announcement-full <?= $isUrgent ? 'is-urgent' : '' ?>">
                <div class="ann-full-badges">
                    <?php if ($isUrgent): ?>
                        <span class="ann-full-badge ann-full-badge--urgent">🔥 URGENT</span>
                    <?php endif; ?>
                    <span class="ann-full-badge ann-full-badge--category" style="--cat-color: <?= h($catColor) ?>; background: <?= h($catColor) ?>;">
                        <?= h($ann['category']) ?>
                    </span>
                </div>
                
                <h2 class="ann-full-title"><?= h($ann['title']) ?></h2>
                
                <div class="ann-full-message"><?= nl2br(h($ann['message'])) ?></div>
                
                <?php if (!empty($images)): ?>
                    <div class="ann-full-images">
                        <?php foreach ($images as $index => $img): ?>
                            <img src="<?= h(get_announcement_image_url($img['image_path'])) ?>" 
                                 alt="Announcement image <?= $index + 1 ?>" 
                                 class="ann-full-image"
                                 onclick="openLightbox(<?= (int)$ann['id'] ?>, <?= $index ?>)"
                                 loading="lazy">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="ann-full-meta">
                    <?php if ($ann['event_date']): ?>
                        <div class="ann-full-meta-row">
                            <strong>📅 Event:</strong>
                            <span><?= h(format_event_date($ann['event_date'])) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ann['event_location']): ?>
                        <div class="ann-full-meta-row">
                            <strong>📍 Location:</strong>
                            <span><?= h($ann['event_location']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ann-full-meta-row">
                        <strong>🕒 Posted:</strong>
                        <span><?= h(date('F j, Y \a\t g:i A', strtotime($ann['created_at']))) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-btn">
                        ← Previous
                    </a>
                <?php else: ?>
                    <span class="pagination-btn is-disabled">← Previous</span>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-btn">
                        Next →
                    </a>
                <?php else: ?>
                    <span class="pagination-btn is-disabled">Next →</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
    <div class="lightbox-content" onclick="event.stopPropagation()">
        <button class="lightbox-close" onclick="closeLightbox()">×</button>
        <button class="lightbox-nav lightbox-prev" onclick="navigateLightbox(-1)">‹</button>
        <img src="" alt="Announcement image" class="lightbox-image" id="lightbox-img">
        <button class="lightbox-nav lightbox-next" onclick="navigateLightbox(1)">›</button>
        <div class="lightbox-counter" id="lightbox-counter"></div>
    </div>
</div>

<script>
// Store images data for lightbox
const announcementImages = <?= json_encode(array_reduce($announcements, function($carry, $ann) {
    $images = get_announcement_images($ann['id']);
    $carry[$ann['id']] = array_map(function($img) {
        return $img['image_path'];
    }, $images);
    return $carry;
}, [])) ?>;

let currentAnnouncementId = null;
let currentImageIndex = 0;

function openLightbox(announcementId, imageIndex) {
    currentAnnouncementId = announcementId;
    currentImageIndex = imageIndex;
    
    const images = announcementImages[announcementId];
    if (!images || !images[imageIndex]) return;
    
    document.getElementById('lightbox-img').src = images[imageIndex];
    document.getElementById('lightbox-counter').textContent = `${imageIndex + 1} / ${images.length}`;
    document.getElementById('lightbox').classList.add('is-open');
    document.body.style.overflow = 'hidden';
}

function closeLightbox(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('lightbox').classList.remove('is-open');
    document.body.style.overflow = '';
}

function navigateLightbox(direction) {
    if (!currentAnnouncementId) return;
    
    const images = announcementImages[currentAnnouncementId];
    if (!images) return;
    
    currentImageIndex += direction;
    
    // Loop around
    if (currentImageIndex < 0) {
        currentImageIndex = images.length - 1;
    } else if (currentImageIndex >= images.length) {
        currentImageIndex = 0;
    }
    
    document.getElementById('lightbox-img').src = images[currentImageIndex];
    document.getElementById('lightbox-counter').textContent = `${currentImageIndex + 1} / ${images.length}`;
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const lightbox = document.getElementById('lightbox');
    if (!lightbox.classList.contains('is-open')) return;
    
    if (e.key === 'Escape') {
        closeLightbox();
    } else if (e.key === 'ArrowLeft') {
        navigateLightbox(-1);
    } else if (e.key === 'ArrowRight') {
        navigateLightbox(1);
    }
});
</script>

<?php render_footer('announcements'); ?>
