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
            <div class="space-y-4">
                <?php foreach ($announcements as $ann): 
                    $catColor = get_category_color($ann['category']);
                    $isUrgent = $ann['priority'] === 'urgent';
                    $images = get_announcement_images($ann['id']);
                ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden <?= $isUrgent ? 'border-l-4 border-red-600' : '' ?>">
                        <div class="p-6">
                            <!-- Badges -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <?php if ($isUrgent): ?>
                                    <span class="px-2.5 py-1 bg-red-600 text-white text-xs font-bold rounded uppercase tracking-wide">
                                        🔥 URGENT
                                    </span>
                                <?php endif; ?>
                                <span class="px-2.5 py-1 text-white text-xs font-bold rounded uppercase tracking-wide" style="background-color: <?= h($catColor) ?>;">
                                    <?= h($ann['category']) ?>
                                </span>
                            </div>
                            
                            <!-- Title -->
                            <h2 class="text-xl font-bold text-gray-900 mb-3 leading-tight">
                                <?= h($ann['title']) ?>
                            </h2>
                            
                            <!-- Message -->
                            <div class="text-gray-700 leading-relaxed mb-4 whitespace-pre-wrap">
                                <?= nl2br(h($ann['message'])) ?>
                            </div>
                            
                            <!-- Images -->
                            <?php if (!empty($images)): ?>
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 my-4">
                                    <?php foreach ($images as $index => $img): ?>
                                        <img 
                                            src="<?= h(get_announcement_image_url($img['image_path'])) ?>" 
                                            alt="Announcement image <?= $index + 1 ?>" 
                                            class="w-full h-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:scale-105 transition-transform"
                                            onclick="openLightbox(<?= (int)$ann['id'] ?>, <?= $index ?>)"
                                            loading="lazy"
                                        >
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Metadata -->
                            <div class="pt-4 border-t border-gray-200 space-y-2 text-sm text-gray-600">
                                <?php if ($ann['event_date']): ?>
                                    <div class="flex items-start gap-2">
                                        <span class="font-semibold text-gray-900 min-w-[80px]">📅 Event:</span>
                                        <span><?= h(format_event_date($ann['event_date'])) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($ann['event_location']): ?>
                                    <div class="flex items-start gap-2">
                                        <span class="font-semibold text-gray-900 min-w-[80px]">📍 Location:</span>
                                        <span><?= h($ann['event_location']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex items-start gap-2">
                                    <span class="font-semibold text-gray-900 min-w-[80px]">🕒 Posted:</span>
                                    <span><?= h(date('F j, Y \a\t g:i A', strtotime($ann['created_at']))) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex items-center justify-center gap-2 mt-8">
                    <?php if ($page > 1): ?>
                        <a 
                            href="?page=<?= $page - 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition text-sm"
                        >
                            ← Previous
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100 border border-gray-200 text-gray-400 rounded-lg font-semibold text-sm cursor-not-allowed">
                            ← Previous
                        </span>
                    <?php endif; ?>
                    
                    <span class="px-4 text-sm text-gray-600">
                        Page <?= $page ?> of <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a 
                            href="?page=<?= $page + 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition text-sm"
                        >
                            Next →
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100 border border-gray-200 text-gray-400 rounded-lg font-semibold text-sm cursor-not-allowed">
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
