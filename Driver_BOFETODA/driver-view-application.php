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

        <a href="driver-renewal.php?start=1" class="btn btn-primary">Re-Apply</a>

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
        $docStmt = $pdo->prepare('SELECT doc_type, file_path, r2_key FROM application_documents WHERE app_no = :app_no');
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
                    <a href="/Driver_BOFETODA/download-attachment.php?app_no=<?php echo urlencode($app_no); ?>&doc_type=<?php echo urlencode($doc['doc_type']); ?>" 
                       class="document-item" 
                       style="display:flex;align-items:center;justify-content:space-between;padding:14px;border:1px solid var(--color-border);border-radius:8px;text-decoration:none;color:var(--color-text);background:#fff;transition:all 0.2s ease;"
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
                                <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">Click to view</div>
                            </div>
                        </div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-text-muted);">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php render_footer('home'); ?>