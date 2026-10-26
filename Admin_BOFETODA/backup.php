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
.backup-wrapper { max-width: 1200px; margin: 0 auto; }

.backup-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 36px; }

.backup-card { 
    background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
    border: 1px solid #e2e8f0; 
    border-radius: 16px; 
    padding: 32px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06); 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.backup-card:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s;
}
.backup-card:hover:before { opacity: 1; }
.backup-card:hover { 
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.08), 0 4px 8px rgba(0,0,0,0.06); 
    border-color: #cbd5e1; 
}

.backup-card__header { display: flex; align-items: flex-start; gap: 18px; margin-bottom: 24px; }
.backup-card__icon { 
    width: 64px; height: 64px; border-radius: 14px; 
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}
.backup-card:hover .backup-card__icon { transform: scale(1.05) rotate(5deg); }
.backup-card__icon--manual { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.backup-card__icon--auto { background: linear-gradient(135deg, #16a34a 0%, #059669 100%); }

.backup-card__content { flex: 1; }
.backup-card__title { 
    font-size: 22px; font-weight: 700; color: #0f172a; 
    margin-bottom: 8px; line-height: 1.3;
    letter-spacing: -0.02em;
}
.backup-card__desc { 
    font-size: 14px; color: #64748b; line-height: 1.8; margin: 0;
}

.backup-btn { 
    display: inline-flex; align-items: center; gap: 10px; justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: white; padding: 16px 28px; border: none; border-radius: 12px; 
    font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
    width: 100%; margin-top: 24px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}
.backup-btn:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3); 
    color: white; 
}
.backup-btn:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3); }
.backup-btn--success { 
    background: linear-gradient(135deg, #16a34a 0%, #059669 100%);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
}
.backup-btn--success:hover { 
    box-shadow: 0 8px 20px rgba(22, 163, 74, 0.3); 
}

.backups-section { 
    background: #ffffff;
    border: 1px solid #e2e8f0; 
    border-radius: 16px; 
    padding: 32px; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06); 
    margin-bottom: 36px;
}
.backups-section__header { 
    display: flex; align-items: center; gap: 16px; 
    padding-bottom: 24px; border-bottom: 2px solid #f1f5f9; margin-bottom: 28px;
}
.backups-section__icon { font-size: 36px; }
.backups-section__title { font-size: 24px; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -0.02em; }

.backups-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.backups-table thead { background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); }
.backups-table th { 
    text-align: left; padding: 16px 18px; font-size: 12px; font-weight: 700; 
    color: #475569; text-transform: uppercase; letter-spacing: 0.8px;
    border-bottom: 2px solid #e2e8f0;
}
.backups-table th:first-child { border-radius: 10px 0 0 0; padding-left: 24px; }
.backups-table th:last-child { border-radius: 0 10px 0 0; padding-right: 24px; }
.backups-table td { 
    padding: 18px; font-size: 14px; border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}
.backups-table td:first-child { padding-left: 24px; }
.backups-table td:last-child { padding-right: 24px; }
.backups-table tbody tr { transition: all 0.2s; }
.backups-table tbody tr:hover { 
    background: linear-gradient(90deg, #fafbfc 0%, #f8fafc 100%);
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}
.backups-table tbody tr:last-child td { border-bottom: none; }

.backup-download-btn { 
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white; padding: 11px 20px; 
    border-radius: 10px; font-size: 13px; font-weight: 600; 
    text-decoration: none; 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(102, 126, 234, 0.2);
}
.backup-download-btn:hover { 
    background: linear-gradient(135deg, #5a67d8 0%, #6b46a0 100%);
    color: white; 
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.loading-spinner { 
    display: inline-block; width: 18px; height: 18px; 
    border: 3px solid rgba(255,255,255,0.3); 
    border-top: 3px solid #ffffff; 
    border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.empty-state { text-align: center; padding: 80px 20px; color: #64748b; }
.empty-state__icon { font-size: 64px; margin-bottom: 20px; opacity: 0.3; }
.empty-state__text { font-size: 15px; line-height: 1.6; }

.best-practices { 
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); 
    border: 1px solid #e2e8f0; 
    border-radius: 16px; 
    padding: 32px; 
    margin-top: 36px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.best-practices__header { 
    display: flex; align-items: center; gap: 14px; 
    margin-bottom: 24px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0;
}
.best-practices__icon { font-size: 32px; }
.best-practices__title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -0.02em; }
.best-practices ul { margin: 0; padding-left: 0; list-style: none; }
.best-practices li { 
    font-size: 14px; color: #475569; margin-bottom: 16px; line-height: 1.8;
    padding-left: 32px; position: relative;
}
.best-practices li:last-child { margin-bottom: 0; }
.best-practices li:before { 
    content: "✓"; position: absolute; left: 0; top: 1px; 
    width: 22px; height: 22px;
    background: linear-gradient(135deg, #16a34a 0%, #059669 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700; 
    font-size: 13px;
    box-shadow: 0 2px 6px rgba(22, 163, 74, 0.2);
}
.best-practices li strong { color: #0f172a; font-weight: 600; }

.info-banner { 
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); 
    border: 1px solid #93c5fd; 
    border-radius: 14px; 
    padding: 24px 28px; 
    margin-bottom: 36px;
    display: flex; 
    align-items: flex-start; 
    gap: 18px;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}
.info-banner__icon { font-size: 32px; flex-shrink: 0; }
.info-banner__content { flex: 1; }
.info-banner__title { 
    font-size: 17px; font-weight: 700; color: #1e40af; 
    margin: 0 0 8px 0; letter-spacing: -0.01em;
}
.info-banner__text { 
    margin: 0; font-size: 14px; color: #1e40af; line-height: 1.8;
}

.backup-stats { 
    display: inline-flex; align-items: center; gap: 8px;
    background: #f1f5f9; color: #475569; padding: 8px 14px; 
    border-radius: 8px; font-size: 13px; margin-top: 20px;
}

@media (max-width: 900px) {
    .backup-grid { grid-template-columns: 1fr; gap: 24px; }
    .backup-card { padding: 28px; }
    .backups-section { padding: 28px; }
    .best-practices { padding: 28px; }
}
</style>
</head>
<body>
<?php render_sidebar('backup'); ?>

<div class="main-col">
<main class="container backup-wrapper">
    <div class="page-head">
        <div>
            <h1 class="page-title"><?= e($page_heading) ?></h1>
            <p class="page-subtitle"><?= e($page_sub) ?></p>
        </div>
    </div>

    <div class="info-banner">
        <div class="info-banner__icon">💾</div>
        <div class="info-banner__content">
            <h3 class="info-banner__title">Backup Information</h3>
            <p class="info-banner__text">Manual backups download immediately to your computer. Automated backups are stored in cloud storage and run daily at midnight. The system automatically keeps the last 7 days of automated backups.</p>
        </div>
    </div>

    <div class="backup-grid">
        <!-- Manual Backup Card -->
        <div class="backup-card">
            <div class="backup-card__header">
                <div class="backup-card__icon backup-card__icon--manual">📥</div>
                <div class="backup-card__content">
                    <h2 class="backup-card__title">Manual Backup</h2>
                    <p class="backup-card__desc">Create an immediate backup and download it to your computer. Use before major changes.</p>
                </div>
            </div>
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
            <div class="backup-card__header">
                <div class="backup-card__icon backup-card__icon--auto">🔄</div>
                <div class="backup-card__content">
                    <h2 class="backup-card__title">Automated Backups</h2>
                    <p class="backup-card__desc">Daily backups run automatically at midnight and are stored securely in cloud storage.</p>
                </div>
            </div>
            <button class="backup-btn backup-btn--success" onclick="refreshBackupList()" id="refreshBtn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Refresh List
            </button>
        </div>
    </div>

    <!-- Automated Backups List -->
    <div class="backups-section">
        <div class="backups-section__header">
            <div class="backups-section__icon">📦</div>
            <h2 class="backups-section__title">Available Backups</h2>
        </div>
        
        <div id="backupsContainer">
            <div class="empty-state">
                <div class="empty-state__icon">⏳</div>
                <div class="empty-state__text">Loading backups...</div>
            </div>
        </div>
    </div>

    <!-- Best Practices -->
    <div class="best-practices">
        <div class="best-practices__header">
            <div class="best-practices__icon">📋</div>
            <h3 class="best-practices__title">Best Practices</h3>
        </div>
        <ul>
            <li><strong>Before major changes:</strong> Always download a manual backup before updating the system, modifying data, or making configuration changes.</li>
            <li><strong>Store backups securely:</strong> Keep downloaded backups in a safe location, preferably on external storage or a secure cloud service.</li>
            <li><strong>Test restores:</strong> Periodically verify that backups can be restored successfully by testing them in a development environment.</li>
            <li><strong>Multiple copies:</strong> Maintain backups in multiple locations (your computer, external drive, cloud) for redundancy.</li>
            <li><strong>Recovery procedure:</strong> To restore a backup, contact your database administrator with the backup file.</li>
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
