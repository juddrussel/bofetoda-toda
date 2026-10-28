<?php

require 'driver-database.php';
require 'driver-template.php';

if (!empty($_SESSION['logged_in'])) {
    header('Location: driver-home.php');
    exit;
}

$show_error = false;
$driver_id_value = '';
$success_message = '';
$session_message = ($_GET['err'] ?? '') === 'account'
    ? 'Your account is no longer active. Please contact the office if you believe this is a mistake.'
    : '';

// Check for password reset success
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success_message = 'Password reset successfully! Please log in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id_value = $_POST['driver_id'] ?? '';
    $password = $_POST['password'] ?? '';
    if (attempt_login($driver_id_value, $password)) {
        // Check if security question is set up
        if (!driver_has_security_question($driver_id_value)) {
            // Redirect to security question setup (mandatory)
            header('Location: driver-setup-security.php');
            exit;
        }
        
        // Security question is set, proceed to home
        header('Location: driver-home.php');
        exit;
    }
    $show_error = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Login | BOFETODA</title>
<style><?php echo APP_CSS; ?></style>
</head>
<body>


<div class="login-screen">
    <div class="login-hero">
        <div class="login-logos">
            <img src="bocaue-seal.png?v=3" alt="Bayan ng Bocaue seal" class="login-logo">
            <img src="bofetoda-seal.png?v=3" alt="BOFETODA seal" class="login-logo">
        </div>
        <h1>BOCAUE TODA INC.</h1>
        <p>Tricycle Membership Permit<br>Management System</p>
    </div>

    <div class="login-panel">
        <div class="login-panel-header">
            <h2>Driver&rsquo;s Portal</h2>
            <p>PLEASE ENTER YOUR CREDENTIALS BELOW</p>
        </div>

        <form action="driver-login.php" method="post" autocomplete="off" id="login-form" novalidate>
            <div class="form-group" data-field="driver_id">
                <label class="form-label" for="driver_id">Driver ID</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input class="form-control" type="text" id="driver_id" name="driver_id" placeholder="DRV-0001" value="<?php echo h($driver_id_value); ?>">
                </div>
                <span class="field-error" id="err-driver_id">Driver ID is required.</span>
            </div>

            <div class="form-group" data-field="password">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input class="form-control" type="password" id="password" name="password" placeholder="Enter Password">
                    <button type="button" class="toggle-visibility" data-target="password" aria-label="Show password">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <span class="field-error" id="err-password">Password is required.</span>
            </div>

            <?php if ($show_error): ?>
            <p class="form-error">Invalid Driver ID or password. Please try again.</p>
            <?php elseif ($session_message): ?>
            <p class="form-error"><?php echo h($session_message); ?></p>
            <?php elseif ($success_message): ?>
            <p class="form-success"><?php echo h($success_message); ?></p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="margin-top: 12px;">Login</button>
            
            <div class="forgot-password-link">
                <a href="driver-forgot-password.php">Forgot Password?</a>
            </div>
            
            <div style="text-align: center; margin-top: 16px;">
                <a href="/" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #6b7280; text-decoration: none; font-weight: 600; transition: color .15s;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Home
                </a>
            </div>
        </form>

    </div>
</div>

<script><?php echo APP_JS; ?></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('login-form');
  if (!form) return;

  function setError(fieldName, isValid) {
    var wrapper = form.querySelector('[data-field="' + fieldName + '"]');
    wrapper.classList.toggle('has-error', !isValid);
  }

  form.querySelectorAll('.form-control').forEach(function (input) {
    input.addEventListener('input', function () {
      setError(input.name, true);
    });
  });

  form.addEventListener('submit', function (e) {
    var driverId = document.getElementById('driver_id');
    var password = document.getElementById('password');
    var ok = true;

    if (driverId.value.trim().length === 0) { setError('driver_id', false); ok = false; }
    if (password.value.length === 0) { setError('password', false); ok = false; }

    if (!ok) e.preventDefault();
  });
});
</script>
</body>
</html>