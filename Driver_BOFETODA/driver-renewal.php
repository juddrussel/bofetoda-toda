<?php

require 'driver-database.php';
require 'driver-template.php';
require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';

$driver = require_driver();
$existingAppNo = get_current_application($driver['driver_id']);
$existingApp   = $existingAppNo ? get_application_detail($existingAppNo) : null;
$existingStatus = $existingApp['status'] ?? null;

// Debug logging
error_log("driver-renewal.php: driver_id={$driver['driver_id']}, existingAppNo=$existingAppNo, existingStatus=$existingStatus");

if ($existingStatus
    && in_array($existingStatus, ['Approved', 'Rejected'], true)
    && !application_is_current_cycle($existingApp)
) {
    $existingAppNo = null;
    $existingApp = null;
    $existingStatus = null;
    error_log("driver-renewal.php: Cleared old Approved/Rejected application");
}

if ($existingStatus && !in_array($existingStatus, ['Approved', 'Rejected'], true)) {
    error_log("driver-renewal.php: Redirecting to view page - status=$existingStatus");
    header('Location: driver-view-application.php');
    exit;
}
$startRequested = $existingStatus === 'Rejected' && (
    isset($_GET['start'])
    || (int) ($_GET['step'] ?? 0) > 1
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']))
);

if ($existingStatus && !$startRequested) {
    render_header([
        'title'          => 'Submit Renewal',
        'topbar_mode'    => 'page',
        'topbar_heading' => 'Submit Renewal Application',
        'topbar_sub'     => $existingAppNo,
    ]);

    if ($existingStatus === 'Rejected'):
        ?>
<div class="content">
    <div class="card" style="position:relative;">
        <span class="badge badge--rejected status-tag">REJECTED</span>
        <div class="card-title" style="margin-bottom:2px;">Admin Remarks</div>
        <p style="font-weight:700;font-size:14px;margin-top:16px;margin-bottom:8px;"><?php echo h($existingApp['reject_reason'] ?? 'Application Rejected'); ?></p>
        <p class="text-muted" style="font-size:13px;"><?php echo h($existingApp['reject_note'] ?: 'Please review and resubmit your application.'); ?></p>
        <a href="driver-renewal.php?start=1" class="card-cta">Re-Apply</a>
    </div>
</div>
        <?php
    else: // Approved
        ?>
<div class="content">
    <div class="card" style="position:relative;">
        <span class="badge badge--approved status-tag">APPROVED</span>
        <div class="card-title" style="margin-bottom:2px;">Current Status</div>
        <p class="text-muted" style="font-size:13px;margin-top:16px;">Your renewal application <?php echo h($existingAppNo); ?> has already been approved. There's nothing to submit right now.</p>
        <a href="driver-view-application.php" class="card-cta">View Details</a>
    </div>
</div>
        <?php
    endif;

    render_footer('submit');
    exit;
}

$openAnnouncement = null;
foreach (get_open_announcement() ?? [] as $a) {
    $openAnnouncement = $a;
    break;
}

if (!$openAnnouncement) {
   
    $upcomingAnnouncement = null;
    foreach (get_upcoming_announcement() ?? [] as $a) {
        $upcomingAnnouncement = $a;
        break;
    }

    render_header([
        'title'          => 'Submit Renewal',
        'topbar_mode'    => 'page',
        'topbar_heading' => 'Submit Renewal Application',
    ]);
    ?>
<div class="content">
    <div class="card">
        <?php if ($upcomingAnnouncement): ?>
            <p class="text-muted" style="font-size:13px;">The next renewal period, <strong><?php echo h($upcomingAnnouncement['title']); ?></strong>, opens on <strong><?php echo h($upcomingAnnouncement['opened']); ?></strong>. Come back then to apply.</p>
        <?php else: ?>
            <p class="text-muted" style="font-size:13px;">There&rsquo;s no renewal period open right now. Check back once the next renewal announcement is posted.</p>
        <?php endif; ?>
        <a href="driver-home.php" class="btn btn-outline" style="margin-top:14px;">Back to Home</a>
    </div>
</div>
    <?php
    render_footer('submit');
    exit;
}

$step = (int) ($_GET['step'] ?? 1);
if ($step < 1 || $step > 3) {
    $step = 1;
}

$upload_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $fields = ['drivers_license', 'payment_receipt', 'or_cr', 'picture_1x1'];

    $ok = true;
    foreach ($fields as $f) {
        if (empty($_FILES[$f]) || $_FILES[$f]['error'] !== UPLOAD_ERR_OK) {
            $ok = false;
            break;
        }
    }

    if ($ok) {
        // Use R2 batch upload with fallback to local storage
        $uploadResult = batch_upload_driver_documents($driver['driver_id'], [
            'drivers_license' => $_FILES['drivers_license'],
            'payment_receipt' => $_FILES['payment_receipt'],
            'or_cr' => $_FILES['or_cr'],
            'picture_1x1' => $_FILES['picture_1x1']
        ]);

        if ($uploadResult['success']) {
            try {
                error_log("=== RENEWAL SUBMISSION START ===");
                error_log("Driver ID: {$driver['driver_id']}");
                error_log("Document paths: " . json_encode($uploadResult['paths']));
                
                $appNo = submit_renewal_application($driver, $uploadResult['paths']);
                
                error_log("Application submitted successfully: $appNo");
                error_log("=== RENEWAL SUBMISSION END ===");
                
                header('Location: driver-home.php?state=submitted');
                exit;
            } catch (Throwable $e) {
                error_log('=== RENEWAL SUBMISSION FAILED ===');
                error_log('Error: ' . $e->getMessage());
                error_log('File: ' . $e->getFile() . ':' . $e->getLine());
                error_log('Trace: ' . $e->getTraceAsString());
                error_log('=== END ERROR ===');
                $upload_error = 'Something went wrong while submitting. Please try again. Error: ' . $e->getMessage();
                $step = 3; // Stay on step 3 to show error
            }
        } else {
            // Log specific errors
            error_log('=== UPLOAD FAILED ===');
            foreach ($uploadResult['errors'] as $docType => $error) {
                error_log("Upload failed for $docType: $error");
            }
            error_log('=== END UPLOAD ERRORS ===');
            $upload_error = 'Something went wrong while saving your documents. Please try again.';
        }
    } else {
        $upload_error = 'Please attach all 4 documents before submitting.';
    }

    $step = 3;
}

render_header([
    'title'          => "Submit Renewal - Step $step",
    'topbar_mode'    => 'simple',
    'topbar_heading' => 'Submit Renewal',
]);
?>

<!-- BEGIN: StepperIndicator -->
<div class="px-4 py-3.5 bg-slate-50">
  <div class="bg-white rounded-2xl p-3 border border-slate-100 shadow-sm">
    <!-- Step Badge in Header -->
    <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
      <div class="text-xs font-semibold text-slate-600">Annual Franchise Clearance</div>
      <div class="bg-amber-50 text-amber-700 border border-amber-200/70 font-bold text-xs px-3 py-1.5 rounded-full shadow-sm">
        Step <?php echo $step; ?> of 3
      </div>
    </div>
    <!-- Step Titles & Icons -->
    <div class="grid grid-cols-3 text-xs font-semibold pb-2">
      <!-- Step 1 -->
      <div class="flex items-center justify-center space-x-1.5 <?php echo $step === 1 ? 'text-amber-600' : 'text-slate-400'; ?>">
        <span class="w-5 h-5 rounded-full <?php echo $step === 1 ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-400'; ?> text-[11px] font-bold flex items-center justify-center">1</span>
        <span class="<?php echo $step === 1 ? 'font-bold' : 'font-normal'; ?> tracking-tight">Profile</span>
      </div>
      <!-- Step 2 -->
      <div class="flex items-center justify-center space-x-1.5 <?php echo $step === 2 ? 'text-amber-600' : 'text-slate-400'; ?>">
        <span class="w-5 h-5 rounded-full <?php echo $step === 2 ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-400'; ?> text-[11px] font-bold flex items-center justify-center">2</span>
        <span class="<?php echo $step === 2 ? 'font-bold' : 'font-normal'; ?>">Vehicle</span>
      </div>
      <!-- Step 3 -->
      <div class="flex items-center justify-center space-x-1.5 <?php echo $step === 3 ? 'text-amber-600' : 'text-slate-400'; ?>">
        <span class="w-5 h-5 rounded-full <?php echo $step === 3 ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-400'; ?> text-[11px] font-bold flex items-center justify-center">3</span>
        <span class="<?php echo $step === 3 ? 'font-bold' : 'font-normal'; ?>">Docs</span>
      </div>
    </div>
    <!-- Progress Bars -->
    <div class="grid grid-cols-3 gap-2 px-1 pt-1">
      <div class="h-1.5 <?php echo $step >= 1 ? 'bg-amber-500' : 'bg-slate-200'; ?> rounded-full"></div>
      <div class="h-1.5 <?php echo $step >= 2 ? 'bg-amber-500' : 'bg-slate-200'; ?> rounded-full"></div>
      <div class="h-1.5 <?php echo $step >= 3 ? 'bg-amber-500' : 'bg-slate-200'; ?> rounded-full"></div>
    </div>
  </div>
</div>
<!-- END: StepperIndicator -->

<!-- BEGIN: MainContent -->
<main class="px-4 flex-1 space-y-4 pb-6">

    <?php if ($step === 1): ?>

        <!-- Profile Information Card -->
        <section class="bg-white rounded-2xl border border-slate-150 p-4 shadow-card">
            <!-- Card Header with Verified Badge -->
            <div class="flex items-start justify-between pb-3.5 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold text-slate-900 leading-tight">Driver's Profile</h2>
                    <p class="text-xs text-slate-400 font-normal mt-0.5">Personal Information on Record</p>
                </div>
                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 px-2.5 py-1 rounded-full text-[11px] font-semibold">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    Verified ID
                </span>
            </div>

            <!-- Form Read-only Content -->
            <div class="mt-3.5 space-y-3">
                <!-- Field: Driver ID -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Driver ID</label>
                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-slate-800">
                        <div class="flex items-center space-x-2.5">
                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <span class="font-bold text-sm tracking-wide text-slate-900"><?php echo h($driver['driver_id']); ?></span>
                        </div>
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 bg-slate-200/70 px-2 py-0.5 rounded">LOCKED</span>
                    </div>
                </div>

                <!-- Field: Full Name -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-slate-800">
                        <div class="flex items-center space-x-2.5 overflow-hidden">
                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <span class="text-sm font-medium text-slate-900 truncate"><?php echo h($driver['name']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Two-column: Age & Birthday -->
                <div class="grid grid-cols-2 gap-2.5">
                    <!-- Age -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Age</label>
                        <div class="bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-900">
                            <?php echo h($driver['age']); ?>
                        </div>
                    </div>
                    <!-- Birthday -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Birthday</label>
                        <div class="bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-900">
                            <?php echo h($driver['birthday']); ?>
                        </div>
                    </div>
                </div>

                <!-- Field: Contact Number -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Contact No.</label>
                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-slate-800">
                        <div class="flex items-center space-x-2.5">
                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <span class="text-sm font-medium text-slate-900"><?php echo h($driver['contact_no']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Field: Address -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Registered Address</label>
                    <div class="flex items-start space-x-2.5 bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-slate-800">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span class="text-sm font-medium text-slate-800 leading-snug">
                            <?php echo h($driver['address']); ?>
                        </span>
                    </div>
                </div>

                <!-- Notice / Disclaimer Box -->
                <div class="bg-amber-50/70 border border-amber-200/70 rounded-xl p-3 mt-3 flex items-start space-x-2.5">
                    <div class="mt-0.5 flex-shrink-0">
                        <span class="w-4 h-4 rounded-full bg-amber-500 text-white font-bold text-[11px] flex items-center justify-center leading-none">i</span>
                    </div>
                    <p class="text-[12px] leading-relaxed text-amber-900/90 font-medium">
                        This information is on file and shown here for review only — it can't be edited on this page. If any information needs updating, please contact TODA Admin.
                    </p>
                </div>
            </div>
        </section>

        <!-- BEGIN: ActionButtons -->
        <div class="pt-2 space-y-2.5">
            <!-- Next Button -->
            <button type="button" id="btn-step1-next" class="w-full bg-amber-500 hover:bg-amber-600 active:scale-[0.99] text-slate-900 font-bold text-base py-3.5 px-4 rounded-xl shadow-btn flex items-center justify-center space-x-2 transition duration-150">
                <span>Next</span>
                <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
            <!-- Subtle Back to Portal / Cancel Link -->
            <a href="driver-home.php" class="block w-full py-2.5 text-center text-xs font-semibold text-slate-500 hover:text-slate-700 transition">
                Return to Dashboard
            </a>
        </div>
        <!-- END: ActionButtons -->

    <?php elseif ($step === 2): ?>

        <!-- Vehicle Information Card -->
        <section class="bg-white rounded-2xl border border-slate-150 p-4 shadow-card">
            <!-- Card Header -->
            <div class="pb-3.5 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900 leading-tight">Vehicle's Information</h2>
                <p class="text-xs text-slate-400 font-normal mt-0.5">Registered Vehicle Details</p>
            </div>

            <!-- Form Read-only Content -->
            <div class="mt-3.5 space-y-3">
                <!-- Field: Body No. -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Body No.</label>
                    <div class="flex items-center bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-slate-800">
                        <span class="text-sm font-medium text-slate-900"><?php echo h($driver['body_no']); ?></span>
                    </div>
                </div>

                <!-- Field: Barangay/Area -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Barangay/Area</label>
                    <div class="flex items-center bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-slate-800">
                        <span class="text-sm font-medium text-slate-900"><?php echo h($driver['barangay']); ?></span>
                    </div>
                </div>

                <!-- Field: Color Code -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Color Code</label>
                    <div class="flex items-center bg-slate-50 border border-slate-200/80 rounded-xl px-3.5 py-2.5 text-slate-800">
                        <span class="text-sm font-medium text-slate-900"><?php echo h($driver['color_code']); ?></span>
                    </div>
                </div>

                <!-- Notice / Disclaimer Box -->
                <div class="bg-amber-50/70 border border-amber-200/70 rounded-xl p-3 mt-3 flex items-start space-x-2.5">
                    <div class="mt-0.5 flex-shrink-0">
                        <span class="w-4 h-4 rounded-full bg-amber-500 text-white font-bold text-[11px] flex items-center justify-center leading-none">i</span>
                    </div>
                    <p class="text-[12px] leading-relaxed text-amber-900/90 font-medium">
                        This information is on file and shown here for review only — it can't be edited on this page.
                    </p>
                </div>
            </div>
        </section>

        <!-- BEGIN: ActionButtons -->
        <div class="pt-2 space-y-2.5">
            <!-- Next Button -->
            <button type="button" id="btn-step2-next" class="w-full bg-amber-500 hover:bg-amber-600 active:scale-[0.99] text-slate-900 font-bold text-base py-3.5 px-4 rounded-xl shadow-btn flex items-center justify-center space-x-2 transition duration-150">
                <span>Next</span>
                <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
            <!-- Back Button -->
            <a href="driver-renewal.php?step=1" class="block w-full py-3 text-center text-sm font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                Back
            </a>
        </div>
        <!-- END: ActionButtons -->

    <?php else: // Step 3 ?>

        <?php if ($upload_error): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-3.5 mb-4 flex items-start space-x-2.5">
            <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <p class="text-sm font-semibold text-red-800"><?php echo h($upload_error); ?></p>
        </div>
        <?php endif; ?>

        <form id="renewalForm" method="post" action="driver-renewal.php?step=3" enctype="multipart/form-data">
            <!-- Documents Card -->
            <section class="bg-white rounded-2xl border border-slate-150 p-4 shadow-card">
                <!-- Card Header -->
                <div class="pb-3.5 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900 leading-tight">Required Documents</h2>
                    <p class="text-xs text-slate-400 font-normal mt-0.5">Upload all necessary documents</p>
                </div>

                <div class="mt-3.5">
                    <p class="text-xs text-amber-900/90 bg-amber-50/70 border border-amber-200/70 rounded-lg p-3 mb-4">
                        Please ensure all required documents are clearly and properly scanned. Low-quality images or poorly scanned documents may result in rejection.
                    </p>

                    <!-- Upload Grid -->
                    <div class="grid grid-cols-1 gap-3" id="upload-grid">
                        <?php
                        $docs = [
                            'drivers_license' => ["Driver's License", 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                            'payment_receipt' => ['Payment Receipt', 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z'],
                            'or_cr' => ['Updated OR/CR', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            'picture_1x1' => ['1x1 Picture', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ];
                        foreach ($docs as $field => $info):
                            list($label, $iconPath) = $info;
                        ?>
                        <div class="upload-tile-modern border-2 border-dashed border-slate-200 rounded-xl p-3 hover:border-amber-400 transition-colors cursor-pointer" data-doc="<?php echo h($field); ?>">
                            <label for="doc_<?php echo h($field); ?>" class="flex items-start space-x-3 cursor-pointer">
                                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="<?php echo h($iconPath); ?>" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-slate-900"><?php echo $label; ?></div>
                                    <div class="text-xs text-slate-500 mt-0.5 file-name">Tap to upload file</div>
                                    <input type="file" id="doc_<?php echo h($field); ?>" name="<?php echo h($field); ?>" accept="image/*,.pdf" required class="hidden">
                                </div>
                                <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </label>
                            <p class="field-error text-xs text-red-600 mt-2" style="display:none;">File must be a photo or PDF, under 4MB.</p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <p class="text-[11px] text-slate-500 mt-3">Accepted formats: JPG, PNG, or PDF. Maximum file size: 4MB.</p>
                    <p class="field-error text-sm text-red-600 font-semibold mt-2" id="err-documents" style="display:none;">Please attach all 4 documents before submitting.</p>
                </div>
            </section>

            <!-- BEGIN: ActionButtons -->
            <div class="pt-4 space-y-2.5">
                <!-- Submit Button -->
                <button type="button" id="btn-step3-submit" class="w-full bg-amber-500 hover:bg-amber-600 active:scale-[0.99] text-slate-900 font-bold text-base py-3.5 px-4 rounded-xl shadow-btn flex items-center justify-center space-x-2 transition duration-150">
                    <span>Submit Application</span>
                    <svg class="w-5 h-5 text-slate-900" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
                <!-- Back Button -->
                <a href="driver-renewal.php?step=2" class="block w-full py-3 text-center text-sm font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Back
                </a>
                <!-- Cancel Link -->
                <button type="button" data-modal-open="modal-cancel" class="w-full py-2.5 text-center text-xs font-semibold text-slate-500 hover:text-slate-700 transition">
                    Cancel Application
                </button>
            </div>
            <!-- END: ActionButtons -->
        </form>

    <?php endif; ?>

</main>
<!-- END: MainContent -->

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var btn1 = document.getElementById('btn-step1-next');
  if (btn1) {
    btn1.addEventListener('click', function () {
      window.location.href = 'driver-renewal.php?step=2';
    });
  }

  var btn2 = document.getElementById('btn-step2-next');
  if (btn2) {
    btn2.addEventListener('click', function () {
      window.location.href = 'driver-renewal.php?step=3';
    });
  }

  // File upload handling for modern tiles
  document.querySelectorAll('#upload-grid input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var tile = input.closest('.upload-tile-modern');
      var fileNameDisplay = tile ? tile.querySelector('.file-name') : null;
      if (input.files && input.files.length > 0) {
        var file = input.files[0];
        if (fileNameDisplay) {
          fileNameDisplay.textContent = file.name;
          fileNameDisplay.classList.add('text-emerald-600', 'font-semibold');
        }
        if (tile) {
          tile.classList.remove('border-slate-200');
          tile.classList.add('border-emerald-400', 'bg-emerald-50/30');
        }
      }
    });
  });
});
</script>

<?php if ($step === 3): ?>
<style>
.modal-illustration {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}
.modal-illustration svg {
  flex-shrink: 0;
}
</style>
<div class="modal-overlay" id="modal-submit">
    <div class="modal-box">
        <div class="modal-illustration">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="40" cy="40" r="40" fill="rgba(0,0,0,0.08)"/>
                <circle cx="40" cy="40" r="26" fill="rgba(0,0,0,0.08)"/>
                <path d="M27 40L36 49L54 31" stroke="#000" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <p class="modal-title">Do you want to proceed with submitting your renewal application?</p>
        <p class="modal-text">Once submitted, changes can no longer be made.</p>
        <div class="modal-attest-row">
            <input type="checkbox" id="confirmAttest">
            <label for="confirmAttest">I confirm that all Information and Documents are correct.</label>
        </div>
        <div class="btn-row">
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
            <button type="button" id="btn-confirm-submit" class="btn btn-primary" disabled>Submit</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-cancel">
    <div class="modal-box">
        <div class="modal-illustration">
            <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="36" cy="36" r="36" fill="rgba(0,0,0,0.08)"/>
                <path d="M26 26L46 46M46 26L26 46" stroke="#000" stroke-width="4.5" stroke-linecap="round"/>
                <circle cx="36" cy="36" r="16" stroke="#000" stroke-width="2.5" opacity="0.3"/>
            </svg>
        </div>
        <p class="modal-title">Do you want to cancel creating your renewal application?</p>
        <p class="modal-text">Once canceled, all unsaved changes will be lost and the process will not continue.</p>
        <div class="btn-row">
            <button type="button" class="btn btn-outline" data-modal-close>Back</button>
            <a href="driver-home.php" class="btn btn-primary">Cancel</a>
        </div>
    </div>
</div>
<div class="modal-overlay" id="modal-success">
    <div class="modal-box">
        <div class="modal-illustration">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="40" cy="40" r="40" fill="rgba(0,0,0,0.08)"/>
                <circle cx="40" cy="40" r="26" fill="rgba(0,0,0,0.08)"/>
                <path d="M27 40L36 49L54 31" stroke="#000" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <p class="modal-title" style="font-size:20px;">Congratulations!</p>
        <p class="modal-text">Your application has been successfully submitted and recorded in the system.</p>
        <div class="btn-row" style="justify-content:center;">
            <a href="driver-home.php" class="btn btn-primary" style="width:100%;max-width:260px;">Back to Home</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  var MAX_UPLOAD_BYTES = <?php echo (int) MAX_UPLOAD_BYTES; ?>;
  var ALLOWED_DOC_EXT = ['jpg', 'jpeg', 'png', 'pdf'];

  function fileExt(name) {
    return (name.split('.').pop() || '').toLowerCase();
  }

  function validateStep3() {
    var ok = true;
    var errMsg = document.getElementById('err-documents');
    document.querySelectorAll('#upload-grid .upload-tile-modern').forEach(function (tile) {
      var input = tile.querySelector('input[type="file"]');
      var filled = input.files && input.files.length > 0;
      var tileError = tile.querySelector('.field-error');
      var validFile = true;

      if (filled) {
        var file = input.files[0];
        if (ALLOWED_DOC_EXT.indexOf(fileExt(file.name)) === -1 || file.size > MAX_UPLOAD_BYTES) {
          validFile = false;
        }
      }

      if (!filled || !validFile) {
        tile.classList.add('border-red-400', 'bg-red-50/30');
        tile.classList.remove('border-slate-200', 'border-emerald-400', 'bg-emerald-50/30');
      }
      if (tileError) tileError.style.display = (filled && !validFile) ? 'block' : 'none';
      if (!filled || !validFile) ok = false;
    });
    if (errMsg) errMsg.style.display = ok ? 'none' : 'block';
    return ok;
  }

  document.querySelectorAll('#upload-grid input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var tile = input.closest('.upload-tile-modern');
      var tileError = tile ? tile.querySelector('.field-error') : null;
      var fileNameDisplay = tile ? tile.querySelector('.file-name') : null;
      
      if (input.files && input.files.length > 0) {
        var file = input.files[0];
        var badType = ALLOWED_DOC_EXT.indexOf(fileExt(file.name)) === -1;
        var badSize = file.size > MAX_UPLOAD_BYTES;
        
        if (badType || badSize) {
          if (tile) {
            tile.classList.add('border-red-400', 'bg-red-50/30');
            tile.classList.remove('border-slate-200', 'border-emerald-400', 'bg-emerald-50/30');
          }
          if (tileError) tileError.style.display = 'block';
          if (fileNameDisplay) {
            fileNameDisplay.textContent = 'Invalid file';
            fileNameDisplay.classList.remove('text-emerald-600');
            fileNameDisplay.classList.add('text-red-600');
          }
          input.value = '';
        } else {
          if (tile) {
            tile.classList.remove('border-slate-200', 'border-red-400', 'bg-red-50/30');
            tile.classList.add('border-emerald-400', 'bg-emerald-50/30');
          }
          if (tileError) tileError.style.display = 'none';
          if (fileNameDisplay) {
            fileNameDisplay.textContent = file.name;
            fileNameDisplay.classList.remove('text-red-600');
            fileNameDisplay.classList.add('text-emerald-600', 'font-semibold');
          }
        }
      }
    });
  });

  var btn3 = document.getElementById('btn-step3-submit');
  if (btn3) {
    btn3.addEventListener('click', function () {
      if (validateStep3()) openModal('modal-submit');
    });
  }

  // Submit stays disabled inside the confirmation sheet until the
  // attestation checkbox is ticked — same pattern as the president's
  // Approval Workflow attest-and-approve control.
  var attest = document.getElementById('confirmAttest');
  var btnConfirm = document.getElementById('btn-confirm-submit');
  if (attest && btnConfirm) {
    attest.addEventListener('change', function () {
      btnConfirm.disabled = !attest.checked;
    });
  }
  if (btnConfirm) {
    btnConfirm.addEventListener('click', function () {
      var form = document.getElementById('renewalForm');
      if (!form) return;

      btnConfirm.disabled = true;
      btnConfirm.textContent = 'Submitting…';

      // Use traditional form submit instead of fetch to allow PHP redirect
      var confirmInput = document.createElement('input');
      confirmInput.type = 'hidden';
      confirmInput.name = 'confirm';
      confirmInput.value = '1';
      form.appendChild(confirmInput);
      form.submit();
    });
  }

});
</script>

<?php endif; ?>

<?php render_footer('submit'); ?>