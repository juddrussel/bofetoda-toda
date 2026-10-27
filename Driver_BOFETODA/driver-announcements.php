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
    'topbar_mode' => 'none',
    'topbar_heading' => '',
]);
?>

<style>
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
</style>

<!-- Main Content -->
<div class="px-4 py-6 pb-24">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">📢 All Announcements</h1>
            <a href="driver-home.php" class="text-sm font-semibold text-blue-600 hover:text-blue-700">
                ← Back to Home
            </a>
        </div>
        
        <!-- Search and Filter Bar -->
        <form method="GET" action="" class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search Input -->
                <div class="flex-1">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search announcements..." 
                        value="<?= h($search) ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    >
                </div>
                
                <!-- Category Filter -->
                <select 
                    name="category" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white"
                >
                    <option value="">All Categories</option>
                    <option value="Event" <?= $category === 'Event' ? 'selected' : '' ?>>Event</option>
                    <option value="Reminder" <?= $category === 'Reminder' ? 'selected' : '' ?>>Reminder</option>
                    <option value="Update" <?= $category === 'Update' ? 'selected' : '' ?>>Update</option>
                    <option value="News" <?= $category === 'News' ? 'selected' : '' ?>>News</option>
                    <option value="Other" <?= $category === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
                
                <!-- Buttons -->
                <button 
                    type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition text-sm"
                >
                    Search
                </button>
                <a 
                    href="driver-announcements.php" 
                    class="px-6 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition text-sm text-center"
                >
                    Clear
                </a>
            </div>
        </form>
        
        <!-- Results Info -->
        <?php if ($total > 0): ?>
            <div class="text-sm text-gray-600 mb-4">
                Showing <?= count($announcements) ?> of <?= $total ?> announcement<?= $total !== 1 ? 's' : '' ?>
                <?php if ($category): ?>
                    in <strong class="text-gray-900"><?= h($category) ?></strong>
                <?php endif; ?>
                <?php if ($search): ?>
                    matching "<strong class="text-gray-900"><?= h($search) ?></strong>"
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Announcements List -->
        <?php if (empty($announcements)): ?>
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="text-5xl mb-4">🔍</div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Announcements Found</h3>
                <p class="text-gray-600 mb-6">
                    <?php if ($search || $category): ?>
                        Try adjusting your search or filter criteria.
                    <?php else: ?>
                        There are no announcements at this time.
                    <?php endif; ?>
                </p>
                <?php if ($search || $category): ?>
                    <a href="driver-announcements.php" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        View All Announcements
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-4" style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($announcements as $ann): 
                    $catColor = get_category_color($ann['category']);
                    $isUrgent = $ann['priority'] === 'urgent';
                    $images = get_announcement_images($ann['id']);
                ?>
                    <div class="announcement-card-modern" style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); overflow: hidden; <?= $isUrgent ? 'border-left: 4px solid #dc2626;' : '' ?>">
                        <div style="padding: 1.5rem;">
                            <!-- Badges -->
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <?php if ($isUrgent): ?>
                                    <span style="padding: 0.25rem 0.625rem; background-color: #dc2626; color: white; font-size: 0.75rem; font-weight: 700; border-radius: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                        🔥 URGENT
                                    </span>
                                <?php endif; ?>
                                <span style="padding: 0.25rem 0.625rem; color: white; font-size: 0.75rem; font-weight: 700; border-radius: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em; background-color: <?= h($catColor) ?>;">
                                    <?= h($ann['category']) ?>
                                </span>
                            </div>
                            
                            <!-- Title -->
                            <h2 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin-bottom: 0.75rem; line-height: 1.25;">
                                <?= h($ann['title']) ?>
                            </h2>
                            
                            <!-- Message -->
                            <div style="color: #374151; line-height: 1.625; margin-bottom: 1rem; white-space: pre-wrap;">
                                <?= nl2br(h($ann['message'])) ?>
                            </div>
                            
                            <!-- Images -->
                            <?php if (!empty($images)): ?>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.75rem; margin: 1rem 0;">
                                    <?php foreach ($images as $index => $img): ?>
                                        <img 
                                            src="<?= h(get_announcement_image_url($img['image_path'])) ?>" 
                                            alt="Announcement image <?= $index + 1 ?>" 
                                            style="width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e5e7eb; cursor: pointer; transition: transform 0.2s;"
                                            onmouseover="this.style.transform='scale(1.05)'"
                                            onmouseout="this.style.transform='scale(1)'"
                                            onclick="openLightbox(<?= (int)$ann['id'] ?>, <?= $index ?>)"
                                            loading="lazy"
                                        >
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Metadata -->
                            <div style="padding-top: 1rem; border-top: 1px solid #e5e7eb; display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                <?php if ($ann['event_date']): ?>
                                    <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                        <span style="font-weight: 600; color: #111827; min-width: 80px;">📅 Event:</span>
                                        <span><?= h(format_event_date($ann['event_date'])) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($ann['event_location']): ?>
                                    <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                        <span style="font-weight: 600; color: #111827; min-width: 80px;">📍 Location:</span>
                                        <span><?= h($ann['event_location']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                    <span style="font-weight: 600; color: #111827; min-width: 80px;">🕒 Posted:</span>
                                    <span><?= h(date('F j, Y \a\t g:i A', strtotime($ann['created_at']))) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                    <?php if ($page > 1): ?>
                        <a 
                            href="?page=<?= $page - 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                            style="padding: 0.5rem 1rem; background: white; border: 1px solid #d1d5db; color: #374151; border-radius: 0.5rem; font-weight: 600; text-decoration: none; font-size: 0.875rem;"
                        >
                            ← Previous
                        </a>
                    <?php else: ?>
                        <span style="padding: 0.5rem 1rem; background: #f3f4f6; border: 1px solid #e5e7eb; color: #9ca3af; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: not-allowed;">
                            ← Previous
                        </span>
                    <?php endif; ?>
                    
                    <span style="padding: 0 1rem; font-size: 0.875rem; color: #6b7280;">
                        Page <?= $page ?> of <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a 
                            href="?page=<?= $page + 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                            style="padding: 0.5rem 1rem; background: white; border: 1px solid #d1d5db; color: #374151; border-radius: 0.5rem; font-weight: 600; text-decoration: none; font-size: 0.875rem;"
                        >
                            Next →
                        </a>
                    <?php else: ?>
                        <span style="padding: 0.5rem 1rem; background: #f3f4f6; border: 1px solid #e5e7eb; color: #9ca3af; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: not-allowed;">
                            Next →
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
    </div>
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
