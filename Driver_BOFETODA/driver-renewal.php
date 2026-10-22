<?php

require 'driver-database.php';
require 'driver-template.php';

$driver = require_driver();
$existingAppNo = get_current_application($driver['driver_id']);
$existingApp   = $existingAppNo ? get_application_detail($existingAppNo) : null;
$existingStatus = $existingApp['status'] ?? null;

if ($existingStatus
    && in_array($existingStatus, ['Approved', 'Rejected'], true)
    && !application_is_current_cycle($existingApp)
) {
    $existingAppNo = null;
    $existingApp = null;
    $existingStatus = null;
}

if ($existingStatus && !in_array($existingStatus, ['Approved', 'Rejected'], true)) {
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
        $uploadDir = __DIR__ . '/uploads/documents/' . $driver['driver_id'] . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
        $paths = [];
        $tooLarge = false;
        $badType  = false;

        foreach ($fields as $f) {
            $origName = $_FILES[$f]['name'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $ok = false;
                $badType = true;
                break;
            }
            if ($_FILES[$f]['size'] > MAX_UPLOAD_BYTES) {
                $ok = false;
                $tooLarge = true;
                break;
            }
            $destName = $f . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = $uploadDir . $destName;

            if (!move_uploaded_file($_FILES[$f]['tmp_name'], $destPath)) {
                $ok = false;
                break;
            }
            $paths[$f] = 'uploads/documents/' . $driver['driver_id'] . '/' . $destName;
        }

        if ($ok) {
            try {
                submit_renewal_application($driver, $paths);
                header('Location: driver-home.php?state=submitted');
                exit;
            } catch (Throwable $e) {
                error_log('submit_renewal_application failed: ' . $e->getMessage());
                $upload_error = 'Something went wrong while submitting. Please try again.';
            }
        } elseif ($tooLarge) {
            $maxMb = round(MAX_UPLOAD_BYTES / 1024 / 1024);
            $upload_error = "File exceeds the maximum allowed size ({$maxMb}MB). Please upload a smaller file.";
        } elseif ($badType) {
            $upload_error = 'Only JPG, PNG, or PDF files are accepted.';
        } else {
            $upload_error = 'Something went wrong while saving your documents. Please try again.';
        }
    } else {
        $upload_error = 'Please attach all 4 documents before submitting.';
    }

    $step = 3;
}

render_header([
    'title'          => "Submit Renewal - Step $step",
    'topbar_mode'    => 'page',
    'topbar_heading' => 'Submit Renewal Application',
    'topbar_sub'     => get_current_application($driver['driver_id']),
]);
?>

<div class="content">

    <div class="progress-track">
        <div class="progress-track-bar"><span style="width:<?php echo $step * 33; ?>%;"></span></div>
    </div>
    <div class="progress-label"><?php echo $step; ?> out of 3</div>

    <?php if ($step === 1): ?>

        <div class="card">
            <div class="card-title">Driver&rsquo;s Profile</div>

            <div class="readonly-field">
                <label for="f_driver_id">Driver ID</label>
                <input type="text" class="value-box" id="f_driver_id" name="driver_id" value="<?php echo h($driver['driver_id']); ?>" readonly>
            </div>
            <div class="readonly-field">
                <label for="f_name">Name</label>
                <input type="text" class="value-box" id="f_name" name="name" value="<?php echo h($driver['name']); ?>" readonly>
            </div>
            <div class="readonly-field">
                <label for="f_age">Age</label>
                <input type="text" class="value-box" id="f_age" name="age" value="<?php echo h($driver['age']); ?>" readonly>
            </div>
            <div class="readonly-field">
                <label for="f_birthday">Birthday</label>
                <input type="text" class="value-box" id="f_birthday" name="birthday" value="<?php echo h($driver['birthday']); ?>" readonly>
            </div>
            <div class="readonly-field">
                <label for="f_contact_no">Contact No.</label>
                <input type="text" class="value-box" id="f_contact_no" name="contact_no" value="<?php echo h($driver['contact_no']); ?>" readonly>
            </div>
            <div class="readonly-field" style="margin-bottom:0;">
                <label for="f_address">Address</label>
                <input type="text" class="value-box" id="f_address" name="address" value="<?php echo h($driver['address']); ?>" readonly>
            </div>
            <p class="text-muted" style="font-size:11px;margin-top:14px;">This information is on file and shown here for review only &mdash; it can&rsquo;t be edited on this page.</p>
        </div>

        <button type="button" id="btn-step1-next" class="btn btn-accent">Next</button>

    <?php elseif ($step === 2): ?>

        <div class="card">
            <div class="card-title">Vehicle&rsquo;s Information</div>

            <div class="readonly-field">
                <label for="f_body_no">Body No.</label>
                <input type="text" class="value-box" id="f_body_no" name="body_no" value="<?php echo h($driver['body_no']); ?>" readonly>
            </div>
            <div class="readonly-field">
                <label for="f_barangay">Barangay/Area</label>
                <input type="text" class="value-box" id="f_barangay" name="barangay" value="<?php echo h($driver['barangay']); ?>" readonly>
            </div>
            <div class="readonly-field" style="margin-bottom:0;">
                <label for="f_color_code">Color Code</label>
                <input type="text" class="value-box" id="f_color_code" name="color_code" value="<?php echo h($driver['color_code']); ?>" readonly>
            </div>
            <p class="text-muted" style="font-size:11px;margin-top:14px;">This information is on file and shown here for review only &mdash; it can&rsquo;t be edited on this page.</p>
        </div>

        <button type="button" id="btn-step2-next" class="btn btn-accent">Next</button>
        <a href="driver-renewal.php?step=1" class="btn btn-outline" style="margin-top:12px;">Back</a>

    <?php else:?>

        <?php if ($upload_error): ?>
        <div class="card" style="border-color:#d32f2f;background:#fdecea;margin-bottom:14px;">
            <p style="color:#d32f2f;font-size:13px;font-weight:600;"><?php echo h($upload_error); ?></p>
        </div>
        <?php endif; ?>

        <form id="renewalForm" method="post" action="driver-renewal.php?step=3" enctype="multipart/form-data">
        <div class="card">
            <div class="card-title" style="margin-bottom:6px;">Documents</div>
            <p class="text-muted" style="font-size:12px;line-height:1.5;margin-bottom:18px;">
                Please ensure all required documents are clearly and properly scanned.
                Low-quality images or poorly scanned documents may result in the rejection of your renewal application.
            </p>

            <div class="upload-grid" id="upload-grid">
                <?php
                $docs = [
                    'drivers_license' => "Driver&rsquo;s Licence",
                    'payment_receipt' => 'Payment Receipt',
                    'or_cr'           => 'Updated OR/CR',
                    'picture_1x1'     => '1x1 Picture',
                ];
                foreach ($docs as $field => $label): ?>
                <div class="upload-tile" data-doc="<?php echo h($field); ?>">
                    <div class="upload-tile-label"><label for="doc_<?php echo h($field); ?>"><?php echo $label; ?></label></div>
                    <label class="upload-tile-box" for="doc_<?php echo h($field); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span>Choose File</span>
                        <input type="file" id="doc_<?php echo h($field); ?>" name="<?php echo h($field); ?>" accept="image/*,.pdf" required>
                    </label>
                    <p class="field-error" style="display:none;margin-top:8px;">File must be a photo or PDF, under 4MB.</p>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="text-muted" style="font-size:11px;margin-top:12px;">Accepted formats: JPG, PNG, or PDF. Maximum file size: 4MB.</p>
            <p class="field-error" id="err-documents" style="display:none;margin-top:10px;">Please attach all 4 documents before submitting.</p>
        </div>

        <div class="btn-row">
            <button type="button" class="btn btn-outline" data-modal-open="modal-cancel">Cancel</button>
            <button type="button" id="btn-step3-submit" class="btn btn-accent">Submit</button>
        </div>
        </form>

    <?php endif; ?>

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
    document.querySelectorAll('#upload-grid .upload-tile').forEach(function (tile) {
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

      tile.classList.toggle('has-error', !filled || !validFile);
      if (tileError) tileError.style.display = (filled && !validFile) ? 'block' : 'none';
      if (!filled || !validFile) ok = false;
    });
    if (errMsg) errMsg.style.display = ok ? 'none' : 'block';
    return ok;
  }

  document.querySelectorAll('#upload-grid input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var tile = input.closest('.upload-tile');
      var tileError = tile ? tile.querySelector('.field-error') : null;
      if (input.files && input.files.length > 0) {
        var file = input.files[0];
        var badType = ALLOWED_DOC_EXT.indexOf(fileExt(file.name)) === -1;
        var badSize = file.size > MAX_UPLOAD_BYTES;
        if (badType || badSize) {
          if (tile) tile.classList.add('has-error');
          if (tileError) tileError.style.display = 'block';
          input.value = '';
        } else {
          if (tile) tile.classList.remove('has-error');
          if (tileError) tileError.style.display = 'none';
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

      var fd = new FormData(form);
      fd.append('confirm', '1');

      btnConfirm.disabled = true;
      btnConfirm.textContent = 'Submitting…';

      fetch('driver-renewal.php?step=3', {
        method: 'POST',
        body: fd,
      })
      .then(function (res) {
  
        if (!res.ok) throw new Error('Server error');
        // Close submit modal, open success modal.
        closeModal('modal-submit');
        setTimeout(function () { openModal('modal-success'); }, 180);
      })
      .catch(function () {
        btnConfirm.disabled = false;
        btnConfirm.textContent = 'Submit';
        closeModal('modal-submit');
        alert('Something went wrong. Please try again.');
      });
    });
  }

});
</script>

<?php endif; ?>

<?php render_footer('submit'); ?>