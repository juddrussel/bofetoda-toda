<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/r2-storage.php';
require_login();

$officer = current_officer();
$role = $officer['role'] ?? 'secretary';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'message' => trim($_POST['message'] ?? ''),
            'category' => $_POST['category'] ?? 'Other',
            'event_date' => !empty($_POST['event_date']) ? $_POST['event_date'] : null,
            'event_location' => trim($_POST['event_location'] ?? ''),
            'priority' => $_POST['priority'] ?? 'normal'
        ];
        
        $imageFiles = $_FILES['images'] ?? [];
        
        $announcementId = create_general_announcement($data, $imageFiles);
        
        if ($announcementId) {
            $message = 'Announcement created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to create announcement. Please try again.';
            $messageType = 'error';
        }
    }
    
    elseif ($action === 'update') {
        $id = (int) ($_POST['announcement_id'] ?? 0);
        
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'message' => trim($_POST['message'] ?? ''),
            'category' => $_POST['category'] ?? 'Other',
            'event_date' => !empty($_POST['event_date']) ? $_POST['event_date'] : null,
            'event_location' => trim($_POST['event_location'] ?? ''),
            'priority' => $_POST['priority'] ?? 'normal'
        ];
        
        $imageFiles = $_FILES['images'] ?? [];
        
        if (update_general_announcement($id, $data, $imageFiles)) {
            $message = 'Announcement updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update announcement. Please try again.';
            $messageType = 'error';
        }
    }
    
    elseif ($action === 'toggle_status') {
        $id = (int) ($_POST['announcement_id'] ?? 0);
        
        if (toggle_announcement_status($id)) {
            $message = 'Announcement status updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update status. Please try again.';
            $messageType = 'error';
        }
    }
    
    elseif ($action === 'delete') {
        $id = (int) ($_POST['announcement_id'] ?? 0);
        
        if (delete_general_announcement($id)) {
            $message = 'Announcement deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete announcement. Please try again.';
            $messageType = 'error';
        }
    }
    
    elseif ($action === 'delete_image') {
        $imageId = (int) ($_POST['image_id'] ?? 0);
        
        if (delete_announcement_image($imageId)) {
            $message = 'Image deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete image. Please try again.';
            $messageType = 'error';
        }
    }
}

// Get filter parameters
$filters = [
    'category' => $_GET['category'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'search' => trim($_GET['search'] ?? '')
];

// Handle status separately to preserve 0 value
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'active') {
        $filters['is_active'] = 1;
    } elseif ($_GET['status'] === 'inactive') {
        $filters['is_active'] = 0;
    }
}

// Remove empty string filters only (preserve 0)
$filters = array_filter($filters, function($value) {
    return $value !== '' && !is_null($value);
});

// Get announcements
$announcements = get_general_announcements($filters);

// Handle edit mode
$editAnnouncement = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editAnnouncement = get_announcement_by_id($editId);
}

$page_heading = 'Manage Announcements';
$page_sub = 'Create and manage announcements for drivers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Announcements · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.ann-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    gap: 20px;
}

.ann-header h2 {
    font-size: 26px;
    font-weight: 800;
    color: var(--navy);
    margin: 0;
}

.ann-filters {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    background: white;
    padding: 18px 20px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-card);
}

.ann-filters select,
.ann-filters input[type="text"] {
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.ann-filters input[type="text"] {
    flex: 1;
    min-width: 250px;
}

.ann-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.ann-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow-card);
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
    transition: box-shadow 0.2s ease;
}

.ann-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.ann-card.is-inactive {
    opacity: 0.6;
    background: #f9fafb;
}

.ann-card__badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.ann-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
}

.ann-badge--urgent {
    background: #dc2626;
}

.ann-badge--normal {
    background: #6b7280;
}

.ann-badge--category {
    background: var(--cat-color);
}

.ann-badge--inactive {
    background: #9ca3af;
}

.ann-card__title {
    font-size: 18px;
    font-weight: 700;
    color: var(--navy);
    margin: 0;
    line-height: 1.3;
}

.ann-card__message {
    font-size: 14px;
    color: var(--text);
    line-height: 1.6;
    margin: 0;
}

.ann-card__meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 13px;
    color: var(--text-muted);
    padding-top: 8px;
    border-top: 1px solid var(--border);
}

.ann-card__meta-row {
    display: flex;
    gap: 6px;
}

.ann-card__meta strong {
    color: var(--navy);
    font-weight: 600;
}

.ann-card__images {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.ann-card__image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--border);
}

.ann-card__actions {
    display: flex;
    gap: 8px;
    margin-top: auto;
    padding-top: 12px;
}

.ann-card__actions button,
.ann-card__actions .btn {
    padding: 8px 14px;
    font-size: 13px;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: white;
    color: var(--navy);
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-block;
}

.ann-card__actions button:hover,
.ann-card__actions .btn:hover {
    background: var(--navy);
    color: white;
    border-color: var(--navy);
}

.btn-danger {
    background: #fee2e2 !important;
    color: #dc2626 !important;
    border-color: #fca5a5 !important;
}

.btn-danger:hover {
    background: #dc2626 !important;
    color: white !important;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--border);
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

.message-banner {
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.message-banner--success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
}

.message-banner--error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
}

/* Modal Styles */
.modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-backdrop.is-open {
    display: flex;
}

.modal {
    background: white;
    border-radius: var(--radius);
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal__head {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
}

.modal__head h3 {
    margin: 0;
    font-size: 20px;
    color: var(--navy);
}

.modal__body {
    padding: 24px;
}

.modal__foot {
    padding: 16px 24px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--navy);
    margin-bottom: 8px;
}

.form-group .required {
    color: #dc2626;
}

.form-group input[type="text"],
.form-group input[type="datetime-local"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(22,35,61,0.1);
}

.form-group-hint {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 4px;
}

.radio-group {
    display: flex;
    gap: 20px;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: normal;
    cursor: pointer;
}

.file-upload {
    border: 2px dashed var(--border);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.file-upload:hover {
    border-color: var(--navy);
    background: #f9fafb;
}

.file-upload input[type="file"] {
    display: none;
}

.image-preview {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.image-preview__item {
    position: relative;
    width: 80px;
    height: 80px;
}

.image-preview__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--border);
}

.image-preview__remove {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
}

@media (max-width: 768px) {
    .ann-grid {
        grid-template-columns: 1fr;
    }
    
    .ann-filters {
        flex-direction: column;
    }
    
    .ann-filters input[type="text"] {
        min-width: 100%;
    }
}
</style>
</head>
<body>
<?php render_sidebar('general_announcements'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Manage Announcements', $page_sub); ?>

  <div class="page">
    
    <?php if ($message): ?>
        <div class="message-banner message-banner--<?= $messageType ?>">
            <?= $messageType === 'success' ? '✓' : '✗' ?> <?= e($message) ?>
        </div>
    <?php endif; ?>

    <div class="ann-header">
      <div>
        <h2><?= e($page_heading) ?></h2>
        <p style="font-size:13px; color:var(--text-muted); margin-top:4px;"><?= e($page_sub) ?></p>
      </div>
      <button type="button" class="btn btn-primary" onclick="openModal('create')">
        + Create Announcement
      </button>
    </div>

    <!-- Filters -->
    <form class="ann-filters" method="GET" action="">
      <input type="text" name="search" placeholder="Search announcements..." value="<?= e($_GET['search'] ?? '') ?>">
      
      <select name="category">
        <option value="">All Categories</option>
        <option value="Event" <?= ($_GET['category'] ?? '') === 'Event' ? 'selected' : '' ?>>Event</option>
        <option value="Reminder" <?= ($_GET['category'] ?? '') === 'Reminder' ? 'selected' : '' ?>>Reminder</option>
        <option value="Update" <?= ($_GET['category'] ?? '') === 'Update' ? 'selected' : '' ?>>Update</option>
        <option value="News" <?= ($_GET['category'] ?? '') === 'News' ? 'selected' : '' ?>>News</option>
        <option value="Other" <?= ($_GET['category'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
      </select>
      
      <select name="priority">
        <option value="">All Priorities</option>
        <option value="normal" <?= ($_GET['priority'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal</option>
        <option value="urgent" <?= ($_GET['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
      </select>
      
      <select name="status">
        <option value="">All Status</option>
        <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
      
      <button type="submit" class="btn btn-accent">Apply Filters</button>
      <a href="<?= BASE_URL ?>/manage_announcements.php" class="btn btn-outline">Clear</a>
    </form>

    <!-- Announcements Grid -->
    <?php if (empty($announcements)): ?>
        <div class="empty-state">
            <div class="empty-state__icon">📢</div>
            <h3>No Announcements Yet</h3>
            <p>Create your first announcement to share updates with drivers.</p>
            <button type="button" class="btn btn-primary" onclick="openModal('create')">
                Create First Announcement
            </button>
        </div>
    <?php else: ?>
        <div class="ann-grid">
            <?php foreach ($announcements as $ann): 
                $catColor = get_announcement_category_color($ann['category']);
            ?>
                <div class="ann-card <?= $ann['is_active'] ? '' : 'is-inactive' ?>">
                    <div class="ann-card__badges">
                        <span class="ann-badge ann-badge--<?= e($ann['priority']) ?>">
                            <?= $ann['priority'] === 'urgent' ? '🔥 URGENT' : 'NORMAL' ?>
                        </span>
                        <span class="ann-badge ann-badge--category" style="--cat-color: <?= e($catColor) ?>; background: <?= e($catColor) ?>;">
                            <?= e($ann['category']) ?>
                        </span>
                        <?php if (!$ann['is_active']): ?>
                            <span class="ann-badge ann-badge--inactive">INACTIVE</span>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="ann-card__title"><?= e($ann['title']) ?></h3>
                    
                    <p class="ann-card__message">
                        <?= e(truncate_announcement_text($ann['message'], 120)) ?>
                    </p>
                    
                    <?php if ($ann['image_count'] > 0): ?>
                        <div class="ann-card__meta-row">
                            <strong>Images:</strong> <?= (int)$ann['image_count'] ?> attached
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ann['event_date']): ?>
                        <div class="ann-card__meta-row">
                            <strong>Event:</strong> <?= e(date('M j, Y g:i A', strtotime($ann['event_date']))) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ann['event_location']): ?>
                        <div class="ann-card__meta-row">
                            <strong>Location:</strong> <?= e($ann['event_location']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ann-card__meta">
                        <div class="ann-card__meta-row">
                            <strong>Created:</strong> <?= e(date('M j, Y g:i A', strtotime($ann['created_at']))) ?>
                        </div>
                        <?php if ($ann['created_by_name']): ?>
                            <div class="ann-card__meta-row">
                                <strong>By:</strong> <?= e($ann['created_by_name']) ?> (<?= e(ucfirst($ann['created_by_role'])) ?>)
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="ann-card__actions">
                        <button type="button" onclick="openModal('edit', <?= (int)$ann['id'] ?>)">
                            Edit
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle active status?')">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="announcement_id" value="<?= (int)$ann['id'] ?>">
                            <button type="submit">
                                <?= $ann['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="announcement_id" value="<?= (int)$ann['id'] ?>">
                            <button type="submit" class="btn-danger">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

  </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal-backdrop <?= $editAnnouncement ? 'is-open' : '' ?>" id="announcement-modal">
    <div class="modal">
        <div class="modal__head">
            <h3 id="modal-title"><?= $editAnnouncement ? 'Edit Announcement' : 'Create Announcement' ?></h3>
        </div>
        <form method="POST" enctype="multipart/form-data" id="announcement-form">
            <input type="hidden" name="action" id="form-action" value="<?= $editAnnouncement ? 'update' : 'create' ?>">
            <input type="hidden" name="announcement_id" id="form-announcement-id" value="<?= $editAnnouncement ? (int)$editAnnouncement['id'] : '' ?>">
            
            <div class="modal__body">
                <div class="form-group">
                    <label for="title">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required maxlength="200" value="<?= $editAnnouncement ? e($editAnnouncement['title']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="message">Message <span class="required">*</span></label>
                    <textarea id="message" name="message" required><?= $editAnnouncement ? e($editAnnouncement['message']) : '' ?></textarea>
                    <div class="form-group-hint">Share your announcement details here.</div>
                </div>
                
                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="Event" <?= $editAnnouncement && $editAnnouncement['category'] === 'Event' ? 'selected' : '' ?>>Event</option>
                        <option value="Reminder" <?= $editAnnouncement && $editAnnouncement['category'] === 'Reminder' ? 'selected' : '' ?>>Reminder</option>
                        <option value="Update" <?= $editAnnouncement && $editAnnouncement['category'] === 'Update' ? 'selected' : '' ?>>Update</option>
                        <option value="News" <?= $editAnnouncement && $editAnnouncement['category'] === 'News' ? 'selected' : '' ?>>News</option>
                        <option value="Other" <?= $editAnnouncement && $editAnnouncement['category'] === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Priority <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="priority" value="normal" <?= !$editAnnouncement || $editAnnouncement['priority'] === 'normal' ? 'checked' : '' ?>>
                            Normal
                        </label>
                        <label>
                            <input type="radio" name="priority" value="urgent" <?= $editAnnouncement && $editAnnouncement['priority'] === 'urgent' ? 'checked' : '' ?>>
                            Urgent (pinned to top)
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="event_date">Event Date & Time (Optional)</label>
                    <input type="datetime-local" id="event_date" name="event_date" value="<?= $editAnnouncement && $editAnnouncement['event_date'] ? date('Y-m-d\TH:i', strtotime($editAnnouncement['event_date'])) : '' ?>">
                    <div class="form-group-hint">For event-type announcements only</div>
                </div>
                
                <div class="form-group">
                    <label for="event_location">Event Location (Optional)</label>
                    <input type="text" id="event_location" name="event_location" maxlength="255" value="<?= $editAnnouncement ? e($editAnnouncement['event_location'] ?? '') : '' ?>">
                </div>
                
                <?php if ($editAnnouncement && !empty($editAnnouncement['images'])): ?>
                    <div class="form-group">
                        <label>Existing Images</label>
                        <div class="image-preview">
                            <?php foreach ($editAnnouncement['images'] as $img): ?>
                                <div class="image-preview__item" id="existing-img-<?= (int)$img['image_id'] ?>">
                                    <img src="<?= BASE_URL ?>/../<?= e($img['image_path']) ?>" class="image-preview__img" alt="Image">
                                    <button type="button" class="image-preview__remove" onclick="deleteExistingImage(<?= (int)$img['image_id'] ?>)" title="Delete image">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-group-hint">Click × to remove an image</div>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Upload New Images (Optional)</label>
                    <div class="file-upload" onclick="document.getElementById('images').click()">
                        <input type="file" id="images" name="images[]" accept="image/*" multiple onchange="previewImages(this)">
                        <p>📷 Click to upload images</p>
                        <div class="form-group-hint">Max 5 images total, 5MB each (JPG, PNG, GIF, WEBP)</div>
                    </div>
                    <div class="image-preview" id="image-preview"></div>
                </div>
            </div>
            
            <div class="modal__foot">
                <button type="button" class="btn btn-outline" onclick="closeModalAndRedirect()">Cancel</button>
                <button type="submit" class="btn btn-primary"><?= $editAnnouncement ? 'Update' : 'Save' ?> Announcement</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode, announcementId = null) {
    if (mode === 'edit' && announcementId) {
        window.location.href = '<?= BASE_URL ?>/manage_announcements.php?edit=' + announcementId;
        return;
    }
    
    const modal = document.getElementById('announcement-modal');
    modal.classList.add('is-open');
}

function closeModal() {
    const modal = document.getElementById('announcement-modal');
    modal.classList.remove('is-open');
}

function closeModalAndRedirect() {
    window.location.href = '<?= BASE_URL ?>/manage_announcements.php';
}

function previewImages(input) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).slice(0, 5).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const item = document.createElement('div');
                item.className = 'image-preview__item';
                item.innerHTML = `
                    <img src="${e.target.result}" class="image-preview__img" alt="Preview ${index + 1}">
                `;
                preview.appendChild(item);
            };
            reader.readAsDataURL(file);
        });
    }
}

function deleteExistingImage(imageId) {
    if (!confirm('Are you sure you want to delete this image?')) {
        return;
    }
    
    // Create a hidden form to submit the delete request
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'delete_image';
    
    const imageIdInput = document.createElement('input');
    imageIdInput.type = 'hidden';
    imageIdInput.name = 'image_id';
    imageIdInput.value = imageId;
    
    form.appendChild(actionInput);
    form.appendChild(imageIdInput);
    document.body.appendChild(form);
    form.submit();
}

// Close modal when clicking outside
document.getElementById('announcement-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModalAndRedirect();
    }
});

// Auto-hide success message after 5 seconds
<?php if ($message && $messageType === 'success'): ?>
setTimeout(function() {
    const banner = document.querySelector('.message-banner');
    if (banner) {
        banner.style.transition = 'opacity 0.3s ease';
        banner.style.opacity = '0';
        setTimeout(() => banner.remove(), 300);
    }
}, 5000);
<?php endif; ?>
</script>

</body>
</html>
