<?php

require 'driver-database.php';
require 'driver-template.php';
require_once __DIR__ . '/../Admin_BOFETODA/r2-storage.php';

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
} elseif (isset($_GET['avatar'])) {
    $notice = 'Profile picture updated successfully!';
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
]);

$driverInitial = strtoupper(substr($driver['name'], 0, 1));
?>

<!-- Main Scrollable Area -->
<main class="flex-1 px-4 space-y-4 pb-28 pt-2 overflow-y-auto">

    <?php if ($notice): ?>
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-center">
        <p class="text-sm font-semibold text-emerald-800"><?= h($notice) ?></p>
    </div>
    <?php elseif ($error): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-center">
        <p class="text-sm font-semibold text-red-800"><?= h($error) ?></p>
    </div>
    <?php endif; ?>

    <!-- BEGIN: DriverIdentitySection -->
    <!-- Avatar, Name, and Status Badge -->
    <section class="flex flex-col items-center justify-center pt-1 pb-2">
        <!-- Avatar with Edit Badge -->
        <form id="avatarForm" method="post" action="driver-profile.php" enctype="multipart/form-data" class="relative">
            <label for="avatarInput" class="cursor-pointer block" title="Tap to change photo">
                <?php if ($avatarUrl): ?>
                <!-- Show image with fallback to initials on error -->
                <div class="w-20 h-20 rounded-full bg-amber-400 text-slate-900 flex items-center justify-center font-bold text-2xl border-4 border-white shadow-md overflow-hidden">
                    <img src="<?= h($avatarUrl) ?>" alt="Profile photo" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-full h-full flex items-center justify-center" style="display:none;"><?= h($driverInitial) ?></div>
                </div>
                <?php else: ?>
                <!-- Show initials when no avatar -->
                <div class="w-20 h-20 rounded-full bg-amber-400 text-slate-900 flex items-center justify-center font-bold text-2xl border-4 border-white shadow-md">
                    <?= h($driverInitial) ?>
                </div>
                <?php endif; ?>
                <!-- Edit button badge -->
                <button type="button" aria-label="Edit Profile Picture" class="absolute bottom-0 right-0 w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center shadow border-2 border-white hover:bg-slate-800 transition pointer-events-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/>
                    </svg>
                </button>
            </label>
            <input type="file" id="avatarInput" name="avatar" accept="image/png,image/jpeg" class="hidden">
        </form>

        <!-- Name & Member Status -->
        <h2 class="mt-3 text-xl font-extrabold text-slate-900 tracking-tight"><?= h($driver['name']) ?></h2>
        <div class="flex items-center gap-1.5 mt-1">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                Active Member
            </span>
            <span class="text-xs text-slate-400 font-medium">• Driver</span>
        </div>
    </section>
    <!-- END: DriverIdentitySection -->

    <!-- BEGIN: DriversProfileCard -->
    <!-- Personal Details Information Card -->
    <section class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-sm">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-[15px] flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                Driver's Profile
            </h3>
            <span class="text-[11px] font-semibold text-slate-400 tracking-wide uppercase">Verified</span>
        </div>

        <div class="space-y-3">
            <!-- Driver ID -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Driver ID</label>
                <div class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 font-semibold text-sm flex items-center justify-between">
                    <span><?= h($driver['driver_id']) ?></span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                </div>
            </div>

            <!-- Age & Birthday Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Age</label>
                    <div class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-medium">
                        <?= h($driver['age']) ?>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Birthday</label>
                    <div class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-medium">
                        <?= h($driver['birthday']) ?>
                    </div>
                </div>
            </div>

            <!-- Contact Number -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Contact No.</label>
                <div class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-medium flex items-center justify-between">
                    <span><?= h($driver['contact_no']) ?></span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                </div>
            </div>

            <!-- Address -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Address</label>
                <div class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-medium leading-snug">
                    <?= h($driver['address']) ?>
                </div>
            </div>
        </div>
    </section>
    <!-- END: DriversProfileCard -->

    <!-- BEGIN: VehiclesInformationCard -->
    <!-- Vehicle and TODA Assignment Information -->
    <section class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/90 shadow-sm">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-[15px] flex items-center gap-2">
                <!-- Tricycle/Vehicle Icon -->
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                Vehicle's Information
            </h3>
            <span class="text-[11px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">Active Unit</span>
        </div>

        <div class="space-y-3">
            <!-- Body No. -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Body No.</label>
                <div class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-bold text-base tracking-wide flex items-center justify-between">
                    <span><?= h($driver['body_no']) ?></span>
                    <span class="text-xs font-normal text-slate-400">Official Bocaue Plate</span>
                </div>
            </div>

            <!-- Barangay / Area -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Barangay / Area</label>
                <div class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-medium">
                    <?= h($driver['barangay']) ?>
                </div>
            </div>

            <!-- Color Code -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Color Code</label>
                <div class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm font-medium flex items-center justify-between">
                    <span class="font-medium"><?= h($driver['color_code']) ?></span>
                    <div class="flex items-center gap-1.5 bg-<?= strtolower($driver['color_code']) ?>-50 border border-<?= strtolower($driver['color_code']) ?>-200 px-2.5 py-0.5 rounded-full">
                        <span class="w-3 h-3 rounded-full bg-<?= strtolower($driver['color_code']) ?>-400 inline-block shadow-sm"></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END: VehiclesInformationCard -->

    <!-- BEGIN: AccountSecurityActions -->
    <!-- Secondary Settings and Credentials Actions -->
    <section class="pt-1 pb-1">
        <div class="grid grid-cols-2 gap-3 mb-3">
            <!-- Change Password Button -->
            <button class="flex items-center justify-center gap-1.5 py-3 px-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-xl shadow-sm transition active:scale-[0.98]" type="button" data-modal-open="modal-change-password">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                <span>Change Password</span>
            </button>

            <!-- Security Question Button -->
            <button class="flex items-center justify-center gap-1.5 py-3 px-3 bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-xl shadow-sm transition active:scale-[0.98]" type="button" data-modal-open="modal-security-question">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                <span>Security Question</span>
            </button>
        </div>

        <!-- Logout Action -->
        <div class="text-center pt-2">
            <button class="inline-flex items-center gap-1 text-rose-500 hover:text-rose-600 font-bold text-xs tracking-wide py-1.5 px-4 rounded-lg hover:bg-rose-50/60 transition" type="button" data-modal-open="modal-logout">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"/>
                </svg>
                <span>Logout</span>
            </button>
        </div>
    </section>
    <!-- END: AccountSecurityActions -->

</main>

<!-- Logout confirmation modal -->
<div class="modal-overlay" id="modal-logout">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl">
        <p class="text-lg font-bold text-slate-900 text-center mb-2">Are you sure you want to log out?</p>
        <p class="text-sm text-slate-600 text-center mb-6">You will need to log back in to access your account.</p>
        <div class="flex gap-3">
            <button type="button" class="flex-1 py-2.5 px-4 bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 text-sm font-semibold rounded-xl transition" data-modal-close>Cancel</button>
            <a href="driver-logout.php" class="flex-1 py-2.5 px-4 bg-amber-400 hover:bg-amber-500 text-slate-900 text-sm font-bold rounded-xl transition text-center">Log out</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-change-password">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl">
        <p class="text-lg font-bold text-slate-900 text-center mb-5">Change Password</p>
        <form method="post" action="driver-profile.php" id="changePasswordForm" novalidate>
            <input type="hidden" name="action" value="change_password">
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="new_password">New Password</label>
                <input class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" type="password" id="new_password" name="new_password" required minlength="8" placeholder="Enter new password">
                <span class="hidden text-xs text-red-600 mt-1" id="err-new_password">Password must be at least 8 characters.</span>
            </div>
            <div class="mb-5">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="confirm_password">Confirm New Password</label>
                <input class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Confirm password">
                <span class="hidden text-xs text-red-600 mt-1" id="err-confirm_password">Passwords do not match.</span>
            </div>
            <div class="flex gap-3">
                <button type="button" class="flex-1 py-2.5 px-4 bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 text-sm font-semibold rounded-xl transition" data-modal-close>Cancel</button>
                <button type="submit" class="flex-1 py-2.5 px-4 bg-amber-400 hover:bg-amber-500 text-slate-900 text-sm font-bold rounded-xl transition">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-security-question">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl">
        <p class="text-lg font-bold text-slate-900 text-center mb-2">Change Security Question</p>
        <p class="text-xs text-slate-500 text-center mb-5">Update your security question and answer for password recovery.</p>
        <form method="post" action="driver-update-security.php" id="securityQuestionForm" novalidate>
            <input type="hidden" name="return_to" value="driver-profile.php">
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="sq_question">Security Question</label>
                <select class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" id="sq_question" name="question_id" required>
                    <option value="">— Select a question —</option>
                    <?php foreach (SECURITY_QUESTIONS as $qid => $qtext): ?>
                    <option value="<?= $qid ?>"><?= h($qtext) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="hidden text-xs text-red-600 mt-1" id="err-sq_question">Please select a question.</span>
            </div>
            <div class="mb-5">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5" for="sq_answer">Your Answer</label>
                <input class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400" type="text" id="sq_answer" name="answer" placeholder="Enter your answer" autocomplete="off" required>
                <span class="hidden text-xs text-red-600 mt-1" id="err-sq_answer">Answer is required.</span>
                <span class="block text-[11px] text-slate-400 mt-1.5">Answer is case-insensitive</span>
            </div>
            <div class="flex gap-3">
                <button type="button" class="flex-1 py-2.5 px-4 bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 text-sm font-semibold rounded-xl transition" data-modal-close>Cancel</button>
                <button type="submit" class="flex-1 py-2.5 px-4 bg-amber-400 hover:bg-amber-500 text-slate-900 text-sm font-bold rounded-xl transition">Save</button>
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
      var newErr = document.getElementById('err-new_password');
      var confirmErr = document.getElementById('err-confirm_password');
      var ok = true;

      if (newPass.value.length < 8) {
        newErr.classList.remove('hidden');
        ok = false;
      } else {
        newErr.classList.add('hidden');
      }

      var mismatch = confirmPass.value.length < 8 || confirmPass.value !== newPass.value;
      if (mismatch) {
        confirmErr.classList.remove('hidden');
        ok = false;
      } else {
        confirmErr.classList.add('hidden');
      }

      if (!ok) e.preventDefault();
    });
  }

  var sqForm = document.getElementById('securityQuestionForm');
  if (sqForm) {
    sqForm.addEventListener('submit', function (e) {
      var question = document.getElementById('sq_question');
      var answer = document.getElementById('sq_answer');
      var questionErr = document.getElementById('err-sq_question');
      var answerErr = document.getElementById('err-sq_answer');
      var ok = true;

      if (!question.value || question.value === '') {
        questionErr.classList.remove('hidden');
        ok = false;
      } else {
        questionErr.classList.add('hidden');
      }

      if (answer.value.trim().length < 2) {
        answerErr.classList.remove('hidden');
        ok = false;
      } else {
        answerErr.classList.add('hidden');
      }

      if (!ok) e.preventDefault();
    });
  }
});
</script>

<?php render_footer('profile'); ?>