<?php

require 'driver-database.php';
require 'driver-template.php';

$driver  = require_driver();
$app_no  = get_current_application($driver['driver_id']);
$app     = $app_no ? get_application_detail($app_no) : null;

if ($app && in_array($app['status'], ['Approved', 'Rejected'], true) && !application_is_current_cycle($app)) {
    $app_no = null;
    $app = null;
}

$is_rejected = $app && $app['status'] === 'Rejected';

render_header([
    'title'          => 'Application Status',
    'topbar_mode'    => 'page',
    'topbar_heading' => 'Current Status',
    'topbar_sub'     => $app_no,
]);
?>

<div class="content">

    <?php if (!$app): ?>
        <div class="card">
            <p class="text-muted" style="font-size:13px;">You don&rsquo;t have a renewal application on file yet.</p>
            <a href="driver-renewal.php" class="btn btn-accent" style="margin-top:14px;">Start a Renewal</a>
        </div>

    <?php elseif ($is_rejected): ?>
        <div class="card" style="position:relative;">
            <span class="badge badge--rejected status-tag">REJECTED</span>
            <div class="card-title" style="margin-bottom:2px;">Admin Remarks</div>
            <p style="font-weight:700;font-size:14px;margin-top:16px;margin-bottom:8px;"><?php echo h($app['reject_reason'] ?? 'Application Rejected'); ?></p>
            <p class="text-muted" style="font-size:13px;margin-bottom:20px;"><?php echo h($app['reject_note'] ?: 'Please review and resubmit your application.'); ?></p>

            <div class="timeline" style="border-top:1px solid var(--color-border-light);padding-top:10px;">
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Submitted</strong></div>
                    <div class="timeline-right"><?php echo h(date('M j, Y', strtotime($app['submitted_at']))); ?></div>
                </div>
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Rejected</strong><span>By: <?php echo h(ucfirst($app['rejected_by'] ? 'Officer ' . $app['rejected_by'] : '')); ?></span></div>
                    <div class="timeline-right"><?php echo $app['rejected_at'] ? h(date('M j, Y', strtotime($app['rejected_at']))) : '&mdash;'; ?></div>
                </div>
            </div>
        </div>

        <!-- Application Details -->
        <div class="card" style="margin-top:16px;">
            <div class="card-title">Application Details</div>
            
            <div class="detail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
                <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo h($app['name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Body Number</div>
                    <div class="detail-value"><?php echo h($app['body_no']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Age</div>
                    <div class="detail-value"><?php echo h($app['age']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Birthday</div>
                    <div class="detail-value"><?php echo h(date('M j, Y', strtotime($app['birthday']))); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contact Number</div>
                    <div class="detail-value"><?php echo h($app['contact_no']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Barangay</div>
                    <div class="detail-value"><?php echo h($app['barangay'] ?? '—'); ?></div>
                </div>
                <div class="detail-item" style="grid-column:1/-1;">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?php echo h($app['address']); ?></div>
                </div>
            </div>
        </div>

        <!-- Submitted Documents -->
        <?php
        // Get documents for this application
        require_once __DIR__ . '/../Admin_BOFETODA/db.php';
        $pdo = get_pdo();
        $docStmt = $pdo->prepare('SELECT doc_type, file_path FROM application_documents WHERE app_no = :app_no');
        $docStmt->execute(['app_no' => $app_no]);
        $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $docLabels = [
            'license' => "Driver's License",
            'or_cr' => 'OR/CR',
            'receipt' => 'Payment Receipt',
            'photo' => '1x1 Picture'
        ];
        ?>
        
        <div class="card" style="margin-top:16px;">
            <div class="card-title">Submitted Documents</div>
            
            <div class="document-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px;">
                <?php foreach ($documents as $doc): ?>
                    <button type="button"
                       class="document-item" 
                       data-app-no="<?php echo h($app_no); ?>"
                       data-doc-type="<?php echo h($doc['doc_type']); ?>"
                       data-doc-name="<?php echo h($docLabels[$doc['doc_type']] ?? ucfirst($doc['doc_type'])); ?>"
                       style="display:flex;align-items:center;justify-content:space-between;padding:14px;border:1px solid var(--color-border);border-radius:8px;color:var(--color-text);background:#fff;transition:all 0.2s ease;cursor:pointer;width:100%;text-align:left;"
                       onmouseover="this.style.borderColor='var(--color-primary)';this.style.background='#fffdf0';"
                       onmouseout="this.style.borderColor='var(--color-border)';this.style.background='#fff';">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:6px;background:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-black);">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px;"><?php echo h($docLabels[$doc['doc_type']] ?? ucfirst($doc['doc_type'])); ?></div>
                                <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">Click to preview</div>
                            </div>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-text-muted);">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <a href="driver-renewal.php?start=1" class="btn btn-primary" style="margin-top:16px;">Re-Apply</a>

    <?php else: ?>
        <div class="card">
            <div class="stepper">
                <div class="step done">
                    <div class="step-circle">&#10003;</div>
                    <div class="step-label" style="font-size:11px;">Submitted</div>
                </div>
                <div class="step <?php echo $app['status'] !== 'Pending' ? 'done' : ''; ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?php echo $app['status'] !== 'Pending' ? '&#10003;' : '2'; ?></div>
                    <div class="step-label" style="font-size:11px;">Validated</div>
                </div>
                <div class="step <?php echo $app['status'] === 'Approved' ? 'done' : ''; ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?php echo $app['status'] === 'Approved' ? '&#10003;' : '3'; ?></div>
                    <div class="step-label" style="font-size:11px;">Approved</div>
                </div>
                <div class="step <?php echo $app['status'] === 'Approved' ? 'done' : ''; ?>">
                    <div class="step-line"></div>
                    <div class="step-circle"><?php echo $app['status'] === 'Approved' ? '&#10003;' : '4'; ?></div>
                    <div class="step-label" style="font-size:11px;">Done</div>
                </div>
            </div>

            <div class="timeline" style="border-top:1px solid var(--color-border-light);padding-top:10px;">
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Submitted</strong></div>
                    <div class="timeline-right"><?php echo h(date('M j, Y', strtotime($app['submitted_at']))); ?></div>
                </div>
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Under Review</strong><span>Validated by: Secretary</span></div>
                    <div class="timeline-right"><?php echo $app['validated_at'] ? h(date('M j, Y', strtotime($app['validated_at']))) : 'Pending'; ?></div>
                </div>
                <div class="timeline-row">
                    <div class="timeline-left"><strong>For Approval</strong><span>Approved by: President</span></div>
                    <div class="timeline-right"><?php echo $app['approved_at'] ? h(date('M j, Y', strtotime($app['approved_at']))) : 'Pending'; ?></div>
                </div>
                <div class="timeline-row">
                    <div class="timeline-left"><strong>Approved</strong></div>
                    <div class="timeline-right"><?php echo $app['approved_at'] ? h(date('M j, Y', strtotime($app['approved_at']))) : 'Pending'; ?></div>
                </div>
            </div>
        </div>

        <!-- Staff-Assisted Submission Banner -->
        <?php if (!empty($app['is_staff_assisted']) && !empty($app['uploaded_by_officer_id'])): ?>
        <div class="card" style="margin-top:16px; background:#e3f2fd; border:2px solid #2196f3;">
            <div class="card-title" style="color:#1976d2;">Staff-Assisted Submission</div>
            <p style="font-size:14px; color:#1565c0; margin-top:12px;">
                <strong>This application was submitted by an officer on your behalf.</strong>
            </p>
            <div style="margin-top:12px; font-size:13px; color:#1976d2;">
                <div style="margin-bottom:6px;"><strong>Submitted by:</strong> 
                    <?php 
                    require_once __DIR__ . '/../Admin_BOFETODA/db.php';
                    $pdo = get_pdo();
                    $officer = $pdo->prepare('SELECT name FROM officers WHERE officer_id = :id');
                    $officer->execute(['id' => $app['uploaded_by_officer_id']]);
                    $officer_name = $officer->fetchColumn();
                    echo h($officer_name ?: 'Officer');
                    ?>
                </div>
                <div><strong>Date:</strong> <?php echo h(date('M j, Y g:i A', strtotime($app['submitted_at']))); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Application Details -->
        <div class="card" style="margin-top:16px;">
            <div class="card-title">Application Details</div>
            
            <div class="detail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
                <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo h($app['name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Body Number</div>
                    <div class="detail-value"><?php echo h($app['body_no']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Age</div>
                    <div class="detail-value"><?php echo h($app['age']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Birthday</div>
                    <div class="detail-value"><?php echo h(date('M j, Y', strtotime($app['birthday']))); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Contact Number</div>
                    <div class="detail-value"><?php echo h($app['contact_no']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Barangay</div>
                    <div class="detail-value"><?php echo h($app['barangay'] ?? '—'); ?></div>
                </div>
                <div class="detail-item" style="grid-column:1/-1;">
                    <div class="detail-label">Address</div>
                    <div class="detail-value"><?php echo h($app['address']); ?></div>
                </div>
            </div>
        </div>

        <!-- Submitted Documents -->
        <?php
        // Get documents for this application
        require_once __DIR__ . '/../Admin_BOFETODA/db.php';
        $pdo = get_pdo();
        $docStmt = $pdo->prepare('SELECT doc_type, file_path FROM application_documents WHERE app_no = :app_no');
        $docStmt->execute(['app_no' => $app_no]);
        $documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $docLabels = [
            'license' => "Driver's License",
            'or_cr' => 'OR/CR',
            'receipt' => 'Payment Receipt',
            'photo' => '1x1 Picture'
        ];
        ?>
        
        <div class="card" style="margin-top:16px;">
            <div class="card-title">Submitted Documents</div>
            
            <div class="document-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px;">
                <?php foreach ($documents as $doc): ?>
                    <button type="button"
                       class="document-item" 
                       data-app-no="<?php echo h($app_no); ?>"
                       data-doc-type="<?php echo h($doc['doc_type']); ?>"
                       data-doc-name="<?php echo h($docLabels[$doc['doc_type']] ?? ucfirst($doc['doc_type'])); ?>"
                       style="display:flex;align-items:center;justify-content:space-between;padding:14px;border:1px solid var(--color-border);border-radius:8px;color:var(--color-text);background:#fff;transition:all 0.2s ease;cursor:pointer;width:100%;text-align:left;"
                       onmouseover="this.style.borderColor='var(--color-primary)';this.style.background='#fffdf0';"
                       onmouseout="this.style.borderColor='var(--color-border)';this.style.background='#fff';">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:6px;background:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-black);">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px;"><?php echo h($docLabels[$doc['doc_type']] ?? ucfirst($doc['doc_type'])); ?></div>
                                <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">Click to preview</div>
                            </div>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-text-muted);">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Document Preview Modal (Available for all statuses) -->
<div id="documentModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);z-index:9999;padding:20px;overflow-y:auto;">
    <div style="max-width:900px;margin:40px auto;background:white;border-radius:12px;position:relative;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--color-border);">
            <h3 id="modalTitle" style="font-size:18px;font-weight:700;color:var(--color-text);margin:0;">Document Preview</h3>
            <button onclick="closeDocumentModal()" style="background:none;border:none;cursor:pointer;padding:8px;border-radius:6px;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='none'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div id="modalContent" style="padding:24px;min-height:400px;display:flex;align-items:center;justify-content:center;">
            <div style="text-align:center;color:var(--color-text-muted);">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto 12px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <p>Loading document...</p>
            </div>
        </div>
        <div style="display:flex;gap:12px;padding:20px 24px;border-top:1px solid var(--color-border);background:#fafafa;border-radius:0 0 12px 12px;">
            <a id="downloadBtn" href="#" download class="btn btn-accent" style="flex:1;text-align:center;text-decoration:none;padding:12px 24px;font-size:14px;font-weight:600;">
                Download
            </a>
            <button onclick="closeDocumentModal()" class="btn btn-outline" style="flex:1;padding:12px 24px;font-size:14px;font-weight:600;">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// Event delegation for document items
document.addEventListener('click', function(e) {
    const docItem = e.target.closest('.document-item');
    if (docItem) {
        const appNo = docItem.dataset.appNo;
        const docType = docItem.dataset.docType;
        const docName = docItem.dataset.docName;
        openDocumentModal(appNo, docType, docName);
    }
});

function openDocumentModal(appNo, docType, docName) {
    const modal = document.getElementById('documentModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalContent = document.getElementById('modalContent');
    const downloadBtn = document.getElementById('downloadBtn');
    
    // Set title
    modalTitle.textContent = docName;
    
    // Build URL
    const docUrl = '/Driver_BOFETODA/download-application-document.php?app_no=' + encodeURIComponent(appNo) + '&doc_type=' + encodeURIComponent(docType);
    
    // Set download button
    downloadBtn.href = docUrl;
    downloadBtn.download = docName;
    
    // Show loading state
    modalContent.innerHTML = '<div style="text-align:center;color:var(--color-text-muted);"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto 12px;animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10" opacity="0.75"></path></svg><p>Loading document...</p></div>';
    
    // Show modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Load document preview (image only)
    const img = new Image();
    img.onload = function() {
        modalContent.innerHTML = '<img src="' + docUrl + '" style="max-width:100%;height:auto;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);" />';
    };
    img.onerror = function() {
        // Not an image, show download prompt
        modalContent.innerHTML = '<div style="text-align:center;padding:40px;"><svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin:0 auto 16px;color:var(--color-primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg><p style="font-size:16px;font-weight:600;color:var(--color-text);margin-bottom:8px;">' + docName + '</p><p style="font-size:14px;color:var(--color-text-muted);">Preview not available. Click download to view this document.</p></div>';
    };
    img.src = docUrl;
}

function closeDocumentModal() {
    const modal = document.getElementById('documentModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal when clicking outside
document.getElementById('documentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDocumentModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDocumentModal();
    }
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<?php render_footer('home'); ?>