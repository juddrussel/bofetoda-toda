<?php

require 'driver-database.php';
require 'driver-template.php';

$driver = require_driver();

$error  = null;
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatar'])) {
        if (save_driver_avatar($driver['driver_id'], $_FILES['avatar'])) {
            header('Location: driver-profile.php?avatar=ok');
            exit;
        }
        $maxMb = round(MAX_UPLOAD_BYTES / 1024 / 1024);
        $error = "Could not upload photo. Use a JPG or PNG under {$maxMb}MB.";

    } elseif (($_POST['action'] ?? '') === 'change_password') {
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($newPass !== '' && $newPass === $confirm) {
            driver_change_password($driver['driver_id'], $newPass);
            header('Location: driver-profile.php?pwok=1');
            exit;
        }
        $error = 'Passwords did not match.';
    }
}

if (isset($_GET['pwok'])) {
    $notice = 'Password updated successfully.';
} elseif (isset($_GET['sqok'])) {
    $notice = 'Security question updated successfully.';
}

if (isset($_GET['sqerror'])) {
    $sqerror = $_GET['sqerror'];
    if ($sqerror === 'question') $error = 'Please select a valid security question.';
    elseif ($sqerror === 'answer') $error = 'Please provide an answer.';
    elseif ($sqerror === 'short') $error = 'Answer must be at least 2 characters long.';
    else $error = 'Failed to update security question. Please try again.';
}

$avatarUrl = driver_avatar_url($driver['driver_id']);

render_header([
    'title'          => 'Profile',
    'topbar_mode'    => 'simple',
    'topbar_heading' => 'Profile',
]);
?>

<div class="profile-header">
    <form id="avatarForm" method="post" action="driver-profile.php" enctype="multipart/form-data" style="margin:0;">
        <label class="avatar" for="avatarInput" title="Tap to change photo">
            <?php if ($avatarUrl): ?>
                <img src="<?php echo h($avatarUrl); ?>" alt="Profile photo">
            <?php else: ?>
                <?php echo h(driver_initials($driver['name'])); ?>
            <?php endif; ?>
            <span class="avatar-edit-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            </span>
        </label>
        <input type="file" id="avatarInput" name="avatar" accept="image/png,image/jpeg" style="display:none;">
    </form>
    <div class="profile-name"><?php echo h($driver['name']); ?></div>
    <div class="profile-role"><?php echo h($driver['role']); ?></div>
</div>

<div class="content" style="padding-top:10px;">

    <?php if ($notice): ?>
        <p class="form-success" style="text-align:center;"><?php echo h($notice); ?></p>
    <?php elseif ($error): ?>
        <p class="form-error" style="text-align:center;"><?php echo h($error); ?></p>
    <?php endif; ?>

    <div class="info-card">
        <div class="info-card-title">Driver&rsquo;s Profile</div>

        <div class="readonly-field">
            <label>Driver ID</label>
            <div class="value-box"><?php echo h($driver['driver_id']); ?></div>
        </div>
        <div class="readonly-field">
            <label>Age</label>
            <div class="value-box"><?php echo h($driver['age']); ?></div>
        </div>
        <div class="readonly-field">
            <label>Birthday</label>
            <div class="value-box"><?php echo h($driver['birthday']); ?></div>
        </div>
        <div class="readonly-field">
            <label>Contact No.</label>
            <div class="value-box"><?php echo h($driver['contact_no']); ?></div>
        </div>
        <div class="readonly-field" style="margin-bottom:0;">
            <label>Address</label>
            <div class="value-box"><?php echo h($driver['address']); ?></div>
        </div>
    </div>

    <div class="info-card">
        <div class="info-card-title">Vehicle&rsquo;s Information</div>

        <div class="readonly-field">
            <label>Body No.</label>
            <div class="value-box"><?php echo h($driver['body_no']); ?></div>
        </div>
        <div class="readonly-field">
            <label>Barangay/Area</label>
            <div class="value-box"><?php echo h($driver['barangay']); ?></div>
        </div>
        <div class="readonly-field" style="margin-bottom:0;">
            <label>Color Code</label>
            <div class="value-box"><?php echo h($driver['color_code']); ?></div>
        </div>
    </div>

    <div style="display:flex; gap:10px; margin-bottom:16px;">
        <button type="button" class="btn btn-outline" data-modal-open="modal-change-password" style="flex:1;">Change Password</button>
        <button type="button" class="btn btn-outline" data-modal-open="modal-security-question" style="flex:1;">Security Question</button>
    </div>

    <a href="#" class="logout-link" data-modal-open="modal-logout">Logout</a>
</div>

<!-- Logout confirmation modal -->
<div class="modal-overlay" id="modal-logout">
    <div class="modal-box">
        <p class="modal-title">Are you sure you want to log out?</p>
        <p class="modal-text">You will need to log back in to access your account.</p>
        <div class="btn-row">
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
            <a href="driver-logout.php" class="btn btn-primary">Log out</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-change-password">
    <div class="modal-box" style="text-align:left;">
        <p class="modal-title" style="text-align:center;margin-bottom:20px;">Change Password</p>
        <form method="post" action="driver-profile.php" id="changePasswordForm" novalidate>
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <input class="form-control form-control--plain" type="password" id="new_password" name="new_password" required minlength="8">
                <span class="field-error" id="err-new_password">Password must be at least 8 characters.</span>
            </div>
            <div class="form-group" style="margin-bottom:8px;">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input class="form-control form-control--plain" type="password" id="confirm_password" name="confirm_password" required minlength="8">
                <span class="field-error" id="err-confirm_password">Passwords do not match.</span>
            </div>
            <div class="btn-row" style="margin-top:14px;">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-security-question">
    <div class="modal-box" style="text-align:left;">
        <p class="modal-title" style="text-align:center;margin-bottom:20px;">Change Security Question</p>
        <form method="post" action="driver-update-security.php" id="securityQuestionForm" novalidate>
            <input type="hidden" name="return_to" value="driver-profile.php">
            <p style="font-size:12px; color:var(--color-text-muted); margin-bottom:16px; text-align:center;">
                Update your security question and answer for password recovery.
            </p>
            <div class="form-group">
                <label class="form-label" for="sq_question">Security Question</label>
                <select class="form-control form-control--plain" id="sq_question" name="question_id" required>
                    <option value="">— Select a question —</option>
                    <?php foreach (SECURITY_QUESTIONS as $qid => $qtext): ?>
                        <option value="<?php echo $qid; ?>"><?php echo h($qtext); ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="field-error" id="err-sq_question">Please select a question.</span>
            </div>
            <div class="form-group" style="margin-bottom:8px;">
                <label class="form-label" for="sq_answer">Your Answer</label>
                <input class="form-control form-control--plain" type="text" id="sq_answer" name="answer" placeholder="Enter your answer" autocomplete="off" required>
                <span class="field-error" id="err-sq_answer">Answer is required.</span>
                <span style="font-size:11px; color:var(--color-text-muted); display:block; margin-top:6px;">Answer is case-insensitive</span>
            </div>
            <div class="btn-row" style="margin-top:14px;">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var avatarInput = document.getElementById('avatarInput');
  var avatarForm  = document.getElementById('avatarForm');
  if (avatarInput && avatarForm) {
    avatarInput.addEventListener('change', function () {
      if (avatarInput.files && avatarInput.files.length) avatarForm.submit();
    });
  }

  var pwForm = document.getElementById('changePasswordForm');
  if (pwForm) {
    pwForm.addEventListener('submit', function (e) {
      var newPass = document.getElementById('new_password');
      var confirmPass = document.getElementById('confirm_password');
      var newGroup = newPass.closest('.form-group');
      var confirmGroup = confirmPass.closest('.form-group');
      var ok = true;

      newGroup.classList.toggle('has-error', newPass.value.length < 8);
      if (newPass.value.length < 8) ok = false;

      var mismatch = confirmPass.value.length < 8 || confirmPass.value !== newPass.value;
      confirmGroup.classList.toggle('has-error', mismatch);
      if (mismatch) ok = false;

      if (!ok) e.preventDefault();
    });
  }

  var sqForm = document.getElementById('securityQuestionForm');
  if (sqForm) {
    sqForm.addEventListener('submit', function (e) {
      var question = document.getElementById('sq_question');
      var answer = document.getElementById('sq_answer');
      var questionGroup = question.closest('.form-group');
      var answerGroup = answer.closest('.form-group');
      var ok = true;

      questionGroup.classList.toggle('has-error', !question.value || question.value === '');
      if (!question.value || question.value === '') ok = false;

      answerGroup.classList.toggle('has-error', answer.value.trim().length < 2);
      if (answer.value.trim().length < 2) ok = false;

      if (!ok) e.preventDefault();
    });
  }
});
</script>

<?php render_footer('profile'); ?>