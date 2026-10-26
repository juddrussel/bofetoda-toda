<?php
/**
 * Database Backup & Recovery
 * Allows president to download manual backups and view automated backups
 * Access: President only
 */

require_once __DIR__ . '/config.php';
require_role('president');

$officer = current_officer();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Database Backup · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.info-banner {
    background:#e0f2fe;
    border:1px solid #7dd3fc;
    border-radius:var(--radius);
    padding:16px 18px;
    margin-bottom:20px;
    display:flex;
    align-items:flex-start;
    gap:12px;
}
.info-banner__icon { font-size:24px; flex-shrink:0; line-height:1; }
.info-banner__content { flex:1; }
.info-banner__title { font-size:13px; font-weight:700; color:#075985; margin:0 0 4px; text-transform:uppercase; letter-spacing:.4px; }
.info-banner__text { margin:0; font-size:13px; color:#075985; line-height:1.6; }

.backup-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }

.loading-spinner {
    display:inline-block;
    width:16px;
    height:16px;
    border:2px solid rgba(255,255,255,0.3);
    border-top-color:#fff;
    border-radius:50%;
    animation:spin 0.8s linear infinite;
}
@keyframes spin { to { transform:rotate(360deg); } }

.empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
.empty-state__icon { font-size:48px; margin-bottom:12px; opacity:0.3; }
.empty-state__text { font-size:14px; }

@media (max-width:900px) {
    .backup-grid { grid-template-columns:1fr; }
}
</style>
</head>
<body>
<?php render_sidebar('backup'); ?>

<div class="main-col">
  <?php render_topbar('Administration Portal', 'Database Backup', 'Manage database backups and ensure data safety'); ?>

<div class="page">

    <div class="info-banner">
        <div class="info-banner__icon">💾</div>
        <div class="info-banner__content">
            <h3 class="info-banner__title">Backup Information</h3>
            <p class="info-banner__text">Manual backups download immediately to your computer. Automated backups are stored in cloud storage and run daily at midnight. The system automatically keeps the last 7 days of automated backups.</p>
        </div>
    </div>

    <div class="backup-grid">
        <!-- Manual Backup Card -->
        <div class="card">
            <div class="card__head">
                <div>
                    <h3>📥 Manual Backup</h3>
                    <p>Create an immediate backup and download it to your computer. Use before major changes.</p>
                </div>
            </div>
            <div class="card__body">
                <a href="<?= BASE_URL ?>/api-manual-backup-php.php" class="btn btn-primary btn-block" id="manualBackupBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Download Backup Now
                </a>
                <p style="font-size:11.5px; color:var(--text-muted); margin-top:12px; text-align:center;">
                    Creates a compressed SQL backup file
                </p>
            </div>
        </div>

        <!-- Automated Backups Card -->
        <div class="card">
            <div class="card__head">
                <div>
                    <h3>🔄 Automated Backups</h3>
                    <p>Daily backups run automatically at midnight and are stored securely in cloud storage.</p>
                </div>
            </div>
            <div class="card__body">
                <button class="btn btn-success btn-block" onclick="refreshBackupList()" id="refreshBtn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    Refresh List
                </button>
            </div>
        </div>
    </div>

    <!-- Automated Backups List -->
    <div class="card">
        <div class="card__head">
            <h3>📦 Available Backups</h3>
        </div>
        <div class="card__body">
            <div id="backupsContainer">
                <div class="empty-state">
                    <div class="empty-state__icon">⏳</div>
                    <div class="empty-state__text">Loading backups...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="card">
        <div class="card__head">
            <h3>📋 Best Practices</h3>
        </div>
        <div class="card__body">
            <ul style="margin:0; padding-left:20px; list-style:disc;">
                <li style="font-size:13.5px; color:var(--text); margin-bottom:12px; line-height:1.7;">
                    <strong>Before major changes:</strong> Always download a manual backup before updating the system, modifying data, or making configuration changes.
                </li>
                <li style="font-size:13.5px; color:var(--text); margin-bottom:12px; line-height:1.7;">
                    <strong>Store backups securely:</strong> Keep downloaded backups in a safe location, preferably on external storage or a secure cloud service.
                </li>
                <li style="font-size:13.5px; color:var(--text); margin-bottom:12px; line-height:1.7;">
                    <strong>Test restores:</strong> Periodically verify that backups can be restored successfully by testing them in a development environment.
                </li>
                <li style="font-size:13.5px; color:var(--text); margin-bottom:12px; line-height:1.7;">
                    <strong>Multiple copies:</strong> Maintain backups in multiple locations (your computer, external drive, cloud) for redundancy.
                </li>
                <li style="font-size:13.5px; color:var(--text); margin-bottom:0; line-height:1.7;">
                    <strong>Recovery procedure:</strong> To restore a backup, contact your database administrator with the backup file.
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
// Load automated backups on page load
document.addEventListener('DOMContentLoaded', function() {
    loadBackupList();
});

async function loadBackupList() {
    const container = document.getElementById('backupsContainer');
    
    try {
        const response = await fetch('<?= BASE_URL ?>/api-list-backups.php');
        const data = await response.json();
        
        if (!data.success) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state__icon">⚠️</div>
                    <div class="empty-state__text">${data.error || 'Failed to load backups'}</div>
                </div>
            `;
            return;
        }
        
        if (data.backups.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state__icon">📦</div>
                    <div class="empty-state__text">No automated backups found yet. The first backup will be created at midnight.</div>
                </div>
            `;
            return;
        }
        
        // Build table
        let html = `
            <div class="table-wrap">
                <table class="registry">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>File Size</th>
                            <th>Age</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.backups.forEach(backup => {
            html += `
                <tr>
                    <td><strong>${backup.date_time || backup.last_modified}</strong></td>
                    <td>${backup.size_formatted}</td>
                    <td class="text-muted">${backup.age}</td>
                    <td style="text-align:right;">
                        <a href="${backup.download_url}" class="btn btn-primary btn-sm" download="${backup.filename}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download
                        </a>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            <p style="margin-top:16px; font-size:12.5px; color:var(--text-muted);">
                Showing ${data.count} backup${data.count !== 1 ? 's' : ''}. Backups older than 7 days are automatically deleted.
            </p>
        `;
        
        container.innerHTML = html;
        
    } catch (error) {
        console.error('Error loading backups:', error);
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state__icon">❌</div>
                <div class="empty-state__text">Error loading backups. Please refresh the page.</div>
            </div>
        `;
    }
}

function refreshBackupList() {
    const btn = document.getElementById('refreshBtn');
    const originalContent = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<div class="loading-spinner"></div> Refreshing...';
    
    loadBackupList().finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
}

// Show loading state when downloading manual backup
document.getElementById('manualBackupBtn').addEventListener('click', function(e) {
    const btn = this;
    const originalContent = btn.innerHTML;
    
    btn.style.pointerEvents = 'none';
    btn.style.opacity = '0.7';
    btn.innerHTML = '<div class="loading-spinner"></div> Creating backup...';
    
    // Reset after 3 seconds (backup should have started downloading by then)
    setTimeout(() => {
        btn.style.pointerEvents = '';
        btn.style.opacity = '';
        btn.innerHTML = originalContent;
    }, 3000);
});
</script>

</div>

<?php render_shared_script(); ?>
</body>
</html>
