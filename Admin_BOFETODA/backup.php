<?php
/**
 * Database Backup & Recovery
 * Allows president to download manual backups and view automated backups
 * Access: President only
 */

require_once __DIR__ . '/config.php';
require_role('president');

$officer = current_officer();
$page_heading = 'Database Backup & Recovery';
$page_sub = 'Manage database backups and ensure data safety';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Database Backup · <?= e(APP_NAME) ?></title>
<?php render_shared_styles(); ?>
<style>
.backup-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 24px; }
.backup-card { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow-card); }
.backup-card__title { font-size: 18px; font-weight: 700; color: var(--navy); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
.backup-card__desc { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; }

.backup-btn { 
    display: inline-flex; align-items: center; gap: 10px; justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: white; padding: 14px 28px; border: none; border-radius: var(--radius); 
    font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none;
    transition: all 0.3s ease; width: 100%;
}
.backup-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); color: white; }
.backup-btn:active { transform: translateY(0); }

.backup-icon { font-size: 24px; }

.backups-list { margin-top: 20px; }
.backups-table { width: 100%; border-collapse: collapse; }
.backups-table th { text-align: left; padding: 12px 10px; font-size: 13px; font-weight: 600; color: var(--text-muted); border-bottom: 2px solid var(--border); }
.backups-table td { padding: 14px 10px; font-size: 14px; border-bottom: 1px solid #f0f0f0; }
.backups-table tr:hover { background: #f9fafb; }

.backup-download-btn { 
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--primary); color: white; padding: 8px 16px; 
    border-radius: 6px; font-size: 13px; font-weight: 500; 
    text-decoration: none; transition: all 0.2s;
}
.backup-download-btn:hover { background: #5a67d8; color: white; transform: translateX(2px); }

.loading-spinner { 
    display: inline-block; width: 16px; height: 16px; 
    border: 2px solid #f3f3f3; border-top: 2px solid var(--primary); 
    border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); }
.empty-state__icon { font-size: 48px; margin-bottom: 12px; opacity: 0.5; }
.empty-state__text { font-size: 14px; }

.best-practices { background: #f9fafb; border-left: 4px solid var(--primary); padding: 20px; border-radius: 4px; margin-top: 30px; }
.best-practices h3 { font-size: 16px; font-weight: 600; color: var(--navy); margin-bottom: 12px; }
.best-practices ul { margin-left: 20px; }
.best-practices li { font-size: 14px; color: var(--text); margin-bottom: 8px; line-height: 1.6; }

.info-banner { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 16px 20px; border-radius: 4px; margin-bottom: 20px; }
.info-banner p { margin: 0; font-size: 14px; color: #1565c0; line-height: 1.6; }
.info-banner strong { font-weight: 600; }
</style>
</head>
<body>
<?php render_sidebar('backup'); ?>

<div class="main-col">
<main class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title"><?= e($page_heading) ?></h1>
            <p class="page-subtitle"><?= e($page_sub) ?></p>
        </div>
    </div>

    <div class="info-banner">
        <p><strong>💾 Backup Information:</strong> Manual backups download immediately to your computer. Automated backups are stored in cloud storage (R2) and run daily at midnight. The system automatically keeps the last 7 days of automated backups.</p>
    </div>

    <div class="backup-grid">
        <!-- Manual Backup Card -->
        <div class="backup-card">
            <h2 class="backup-card__title">
                <span class="backup-icon">📥</span>
                Manual Backup
            </h2>
            <p class="backup-card__desc">
                Create an immediate backup of the entire database and download it to your computer. 
                Use this before making major changes or for extra safety.
            </p>
            <a href="<?= BASE_URL ?>/api-manual-backup.php" class="backup-btn" id="manualBackupBtn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Download Backup Now
            </a>
        </div>

        <!-- Automated Backups Card -->
        <div class="backup-card">
            <h2 class="backup-card__title">
                <span class="backup-icon">🔄</span>
                Automated Backups
            </h2>
            <p class="backup-card__desc">
                Daily backups are automatically created at midnight and stored securely in cloud storage. 
                The last 7 days are kept for recovery purposes.
            </p>
            <button class="backup-btn" onclick="refreshBackupList()" id="refreshBtn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Refresh List
            </button>
        </div>
    </div>

    <!-- Automated Backups List -->
    <div class="backup-card">
        <h2 class="backup-card__title">
            <span class="backup-icon">📦</span>
            Available Backups
        </h2>
        
        <div id="backupsContainer" class="backups-list">
            <div class="empty-state">
                <div class="empty-state__icon">⏳</div>
                <div class="empty-state__text">Loading backups...</div>
            </div>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="best-practices">
        <h3>📋 Best Practices</h3>
        <ul>
            <li><strong>Before major changes:</strong> Always download a manual backup before updating the system, modifying data, or making configuration changes.</li>
            <li><strong>Store backups securely:</strong> Keep downloaded backups in a safe location, preferably on external storage or a secure cloud service.</li>
            <li><strong>Test restores:</strong> Periodically verify that backups can be restored successfully by testing them in a development environment.</li>
            <li><strong>Multiple copies:</strong> Maintain backups in multiple locations (your computer, external drive, cloud) for redundancy.</li>
            <li><strong>Recovery procedure:</strong> To restore a backup, contact your database administrator or system administrator with the backup file.</li>
        </ul>
    </div>
</main>

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
            <table class="backups-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>File Size</th>
                        <th>Age</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.backups.forEach(backup => {
            html += `
                <tr>
                    <td><strong>${backup.date_time || backup.last_modified}</strong></td>
                    <td>${backup.size_formatted}</td>
                    <td>${backup.age}</td>
                    <td>
                        <a href="${backup.download_url}" class="backup-download-btn" download="${backup.filename}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
            <p style="margin-top: 16px; font-size: 13px; color: var(--text-muted);">
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
