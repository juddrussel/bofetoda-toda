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
    'topbar_mode'    => 'simple',
    'topbar_heading' => 'Current Status',
]);

// Get status badge info
$statusColors = [
    'Pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200/70', 'dot' => 'bg-amber-500', 'label' => 'In Progress'],
    'Validated' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200/70', 'dot' => 'bg-blue-500', 'label' => 'Validated'],
    'Approved' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200/70', 'dot' => 'bg-emerald-500', 'label' => 'Approved'],
    'Rejected' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200/70', 'dot' => 'bg-red-500', 'label' => 'Rejected'],
];
$status = $app ? $app['status'] : 'Pending';
$statusStyle = $statusColors[$status] ?? $statusColors['Pending'];
?>

<!-- Page Navigation Header -->
<section class="px-4 pt-4 pb-3 bg-white border-b border-slate-100">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <a href="driver-home.php" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 active:scale-95 transition-all flex items-center justify-center text-slate-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"></path>
                </svg>
            </a>
            <div>
                <?php if ($app_no): ?>
                <p class="text-xs font-semibold text-slate-400 tracking-wider"><?php echo h($app_no); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($app): ?>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold <?php echo $statusStyle['bg']; ?> <?php echo $statusStyle['text']; ?> border <?php echo $statusStyle['border']; ?> shadow-xs">
            <span class="w-1.5 h-1.5 rounded-full <?php echo $statusStyle['dot']; ?> mr-1.5"></span>
            <?php echo $statusStyle['label']; ?>
        </span>
        <?php endif; ?>
    </div>
</section>

<!-- Main Content -->
<div class="px-3.5 py-4 space-y-4 flex-1"

    <?php if (!$app): ?>
        <section class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200/80 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
            <p class="text-sm text-slate-600 mb-4">You don't have a renewal application on file yet.</p>
            <a href="driver-renewal.php" class="inline-block w-full bg-amber-500 hover:bg-amber-600 active:scale-[0.99] text-slate-900 font-bold text-sm py-3 px-4 rounded-xl shadow-btn transition">
                Start a Renewal
            </a>
        </section>

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

        <!-- Application Details Card -->
        <section class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                <h2 class="text-base font-bold text-slate-900 tracking-tight">Application Details</h2>
                <span class="text-[10px] font-semibold tracking-wide uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-600">Franchise Renewal</span>
            </div>
            
            <div class="grid grid-cols-2 gap-y-3.5 gap-x-3 text-xs">
                <!-- Name Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">NAME</span>
                    <span class="font-bold text-slate-900 text-sm leading-snug block"><?php echo h($app['name']); ?></span>
                </div>
                
                <!-- Body Number Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">BODY NUMBER</span>
                    <span class="font-extrabold text-amber-600 text-sm tracking-wide block"><?php echo h($app['body_no']); ?></span>
                </div>
                
                <!-- Age Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">AGE</span>
                    <span class="font-semibold text-slate-800 text-sm leading-snug block"><?php echo h($app['age']); ?></span>
                </div>
                
                <!-- Birthday Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">BIRTHDAY</span>
                    <span class="font-semibold text-slate-800 text-sm leading-snug block"><?php echo h(date('M j, Y', strtotime($app['birthday']))); ?></span>
                </div>
                
                <!-- Contact Number Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">CONTACT NUMBER</span>
                    <span class="font-semibold text-slate-800 text-sm leading-snug block"><?php echo h($app['contact_no']); ?></span>
                </div>
                
                <!-- Barangay Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">BARANGAY</span>
                    <span class="font-semibold text-slate-800 text-sm leading-snug block"><?php echo h($app['barangay'] ?? '—'); ?></span>
                </div>
                
                <!-- Address Field (Full Width) -->
                <div class="col-span-2 pt-1 border-t border-slate-50 space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">ADDRESS</span>
                    <span class="font-medium text-slate-800 text-xs leading-relaxed block"><?php echo h($app['address']); ?></span>
                </div>
            </div>
        </section>

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
        
        <!-- Submitted Documents Card -->
        <section class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80">
            <div class="flex items-center justify-between mb-3.5">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight leading-tight">Submitted Documents</h2>
                    <p class="text-[11px] text-slate-400 font-medium">Verified attachments for this renewal</p>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800"><?php echo count($documents); ?> Files</span>
            </div>
            
            <!-- 2-Column Responsive Document Cards -->
            <div class="grid grid-cols-2 gap-2.5">
                <?php foreach ($documents as $doc): ?>
                <button type="button"
                    class="document-item group flex items-center justify-between p-2.5 rounded-xl border border-slate-200/90 bg-slate-50/60 hover:bg-amber-50/40 hover:border-amber-300 transition-all text-left" 
                    data-app-no="<?php echo h($app_no); ?>"
                    data-doc-type="<?php echo h($doc['doc_type']); ?>"
                    data-doc-name="<?php echo h($docLabels[$doc['doc_type']] ?? ucfirst($doc['doc_type'])); ?>">
                    <div class="flex items-center space-x-2 overflow-hidden">
                        <div class="w-8 h-8 rounded-lg bg-amber-400 text-slate-900 flex-shrink-0 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-bold text-slate-800 truncate leading-tight"><?php echo h($docLabels[$doc['doc_type']] ?? ucfirst($doc['doc_type'])); ?></p>
                            <p class="text-[10px] text-slate-400 font-medium">Click to preview</p>
                        </div>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-amber-600 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
                    </svg>
                </button>
                <?php endforeach; ?>
            </div>
        </section>

        <a href="driver-renewal.php?start=1" class="btn btn-primary" style="margin-top:16px;">Re-Apply</a>

    <?php else: ?>
        <!-- Process Tracker Card -->
        <section class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80">
            <!-- Step Indicator Header -->
            <div class="relative px-2 pt-2 pb-5">
                <!-- Connecting Line Behind Badges (Gray background) -->
                <div class="absolute top-[26px] left-[50px] right-[50px] h-[3px] bg-slate-200 rounded-full"></div>
                
                <!-- Completed Line Portion (Green progress) -->
                <?php 
                $progressWidth = 0;
                if ($status === 'Pending') {
                    $progressWidth = 0; // Only first step complete, no line
                } elseif ($status === 'Validated') {
                    $progressWidth = 33.33; // Line to step 2
                } elseif ($status === 'Approved') {
                    $progressWidth = 100; // Line all the way
                }
                ?>
                <?php if ($progressWidth > 0): ?>
                <div class="absolute top-[26px] left-[50px] h-[3px] bg-emerald-500 rounded-full transition-all duration-500 ease-in-out" style="width: calc(<?php echo $progressWidth; ?>% - 0px); max-width: calc(100% - 100px);"></div>
                <?php endif; ?>
                
                <!-- Step Badges Row -->
                <div class="relative z-10 flex justify-between items-start text-center">
                    <!-- Step 1: Submitted -->
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-emerald-100">
                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <span class="text-[11px] font-bold text-slate-800 mt-2">Submitted</span>
                    </div>
                    
                    <!-- Step 2: Validated -->
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full <?php echo $status === 'Validated' || $status === 'Approved' ? 'bg-emerald-500 text-white shadow-md ring-4 ring-emerald-100' : 'bg-white text-slate-600 border-2 border-slate-300 ring-4 ring-white'; ?> flex items-center justify-center font-semibold text-xs transition-all duration-300">
                            <?php if ($status === 'Validated' || $status === 'Approved'): ?>
                                <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            <?php else: ?>
                                2
                            <?php endif; ?>
                        </div>
                        <span class="text-[11px] <?php echo $status === 'Validated' || $status === 'Approved' ? 'font-bold text-slate-800' : 'font-medium text-slate-500'; ?> mt-2">Validated</span>
                    </div>
                    
                    <!-- Step 3: Approved -->
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full <?php echo $status === 'Approved' ? 'bg-emerald-500 text-white shadow-md ring-4 ring-emerald-100' : 'bg-white text-slate-400 border-2 border-slate-200 ring-4 ring-white'; ?> flex items-center justify-center font-semibold text-xs transition-all duration-300">
                            <?php if ($status === 'Approved'): ?>
                                <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            <?php else: ?>
                                3
                            <?php endif; ?>
                        </div>
                        <span class="text-[11px] <?php echo $status === 'Approved' ? 'font-bold text-slate-800' : 'font-medium text-slate-400'; ?> mt-2">Approved</span>
                    </div>
                    
                    <!-- Step 4: Done -->
                    <div class="flex flex-col items-center">
                        <div class="w-9 h-9 rounded-full <?php echo $status === 'Approved' ? 'bg-emerald-500 text-white shadow-md ring-4 ring-emerald-100' : 'bg-white text-slate-400 border-2 border-slate-200 ring-4 ring-white'; ?> flex items-center justify-center font-semibold text-xs transition-all duration-300">
                            <?php if ($status === 'Approved'): ?>
                                <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            <?php else: ?>
                                4
                            <?php endif; ?>
                        </div>
                        <span class="text-[11px] <?php echo $status === 'Approved' ? 'font-bold text-slate-800' : 'font-medium text-slate-400'; ?> mt-2">Done</span>
                    </div>
                </div>
            </div>
            
            <!-- Timeline Details -->
            <div class="border-t border-slate-100 mt-2 pt-3 divide-y divide-slate-100/90 text-sm">
                <!-- Row 1: Submitted -->
                <div class="py-2.5 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-900">Submitted</span>
                    <span class="text-xs font-medium text-slate-600"><?php echo h(date('M j, Y', strtotime($app['submitted_at']))); ?></span>
                </div>
                
                <!-- Row 2: Under Review -->
                <div class="py-2.5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-900">Under Review</p>
                        <p class="text-[11px] text-slate-400 font-normal">Validated by: Secretary</p>
                    </div>
                    <span class="text-xs font-semibold <?php echo $app['validated_at'] ? 'text-emerald-600 bg-emerald-50' : 'text-amber-600 bg-amber-50'; ?> px-2 py-0.5 rounded">
                        <?php echo $app['validated_at'] ? h(date('M j, Y', strtotime($app['validated_at']))) : 'Pending'; ?>
                    </span>
                </div>
                
                <!-- Row 3: For Approval -->
                <div class="py-2.5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-900">For Approval</p>
                        <p class="text-[11px] text-slate-400 font-normal">Approved by: President</p>
                    </div>
                    <span class="text-xs font-semibold <?php echo $app['approved_at'] ? 'text-emerald-600 bg-emerald-50' : 'text-amber-600 bg-amber-50'; ?> px-2 py-0.5 rounded">
                        <?php echo $app['approved_at'] ? h(date('M j, Y', strtotime($app['approved_at']))) : 'Pending'; ?>
                    </span>
                </div>
                
                <!-- Row 4: Approved -->
                <div class="py-2.5 flex items-center justify-between">
                    <p class="text-xs font-semibold text-slate-900">Approved</p>
                    <span class="text-xs font-semibold <?php echo $app['approved_at'] ? 'text-emerald-600 bg-emerald-50' : 'text-amber-600 bg-amber-50'; ?> px-2 py-0.5 rounded">
                        <?php echo $app['approved_at'] ? h(date('M j, Y', strtotime($app['approved_at']))) : 'Pending'; ?>
                    </span>
                </div>
            </div>
        </section>

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

        <!-- Application Details Card -->
        <section class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                <h2 class="text-base font-bold text-slate-900 tracking-tight">Application Details</h2>
                <span class="text-[10px] font-semibold tracking-wide uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-600">Franchise Renewal</span>
            </div>
            
            <div class="grid grid-cols-2 gap-y-3.5 gap-x-3 text-xs">
                <!-- Name Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">NAME</span>
                    <span class="font-bold text-slate-900 text-sm leading-snug block"><?php echo h($app['name']); ?></span>
                </div>
                
                <!-- Body Number Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">BODY NUMBER</span>
                    <span class="font-extrabold text-amber-600 text-sm tracking-wide block"><?php echo h($app['body_no']); ?></span>
                </div>
                
                <!-- Age Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">AGE</span>
                    <span class="font-semibold text-slate-800 text-sm leading-snug block"><?php echo h($app['age']); ?></span>
                </div>
                
                <!-- Birthday Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">BIRTHDAY</span>
                    <span class="font-semibold text-slate-800 text-sm leading-snug block"><?php echo h(date('M j, Y', strtotime($app['birthday']))); ?></span>
                </div>
                
                <!-- Contact Number Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">CONTACT NUMBER</span>
                    <span class="font-semibold text-slate-800 text-sm leading-snug block"><?php echo h($app['contact_no']); ?></span>
                </div>
                
                <!-- Barangay Field -->
                <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">BARANGAY</span>
                    <span class="font-semibold text-slate-800 text-sm leading-snug block"><?php echo h($app['barangay'] ?? '—'); ?></span>
                </div>
                
                <!-- Address Field (Full Width) -->
                <div class="col-span-2 pt-1 border-t border-slate-50 space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 tracking-wider uppercase block">ADDRESS</span>
                    <span class="font-medium text-slate-800 text-xs leading-relaxed block"><?php echo h($app['address']); ?></span>
                </div>
            </div>
        </section>

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
        
        <!-- Submitted Documents Card -->
        <section class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200/80">
            <div class="flex items-center justify-between mb-3.5">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight leading-tight">Submitted Documents</h2>
                    <p class="text-[11px] text-slate-400 font-medium">Verified attachments for this renewal</p>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800"><?php echo count($documents); ?> Files</span>
            </div>
            
            <!-- 2-Column Responsive Document Cards -->
            <div class="grid grid-cols-2 gap-2.5">
                <?php foreach ($documents as $doc): ?>
                <button type="button"
                    class="document-item group flex items-center justify-between p-2.5 rounded-xl border border-slate-200/90 bg-slate-50/60 hover:bg-amber-50/40 hover:border-amber-300 transition-all text-left" 
                    data-app-no="<?php echo h($app_no); ?>"
                    data-doc-type="<?php echo h($doc['doc_type']); ?>"
                    data-doc-name="<?php echo h($docLabels[$doc['doc_type']] ?? ucfirst($doc['doc_type'])); ?>">
                    <div class="flex items-center space-x-2 overflow-hidden">
                        <div class="w-8 h-8 rounded-lg bg-amber-400 text-slate-900 flex-shrink-0 flex items-center justify-center shadow-xs group-hover:scale-105 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-bold text-slate-800 truncate leading-tight"><?php echo h($docLabels[$doc['doc_type']] ?? ucfirst($doc['doc_type'])); ?></p>
                            <p class="text-[10px] text-slate-400 font-medium">Click to preview</p>
                        </div>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-amber-600 group-hover:translate-x-0.5 transition-all flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
                    </svg>
                </button>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>
<!-- END: MainScrollContent -->

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