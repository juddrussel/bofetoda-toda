<?php

require 'driver-database.php';
require 'driver-template.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine current step
$step = $_GET['step'] ?? 'enter_id';
$error = '';
$info = '';

// ============================================================================
// STEP 1: Enter Driver ID
// ============================================================================
if ($step === 'enter_id') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $driverId = trim($_POST['driver_id'] ?? '');
        
        if ($driverId === '') {
            $error = 'Please enter your Driver ID.';
        } else {
            // Check if driver exists and has security question
            $pdo = get_pdo();
            $stmt = $pdo->prepare(
                'SELECT driver_id, security_question_id, is_archived 
                 FROM drivers 
                 WHERE driver_id = :id'
            );
            $stmt->execute(['id' => $driverId]);
            $driver = $stmt->fetch();
            
            if (!$driver) {
                $error = 'Driver ID not found. Please check and try again.';
            } elseif ((int) $driver['is_archived'] !== 0) {
                $error = 'This account is archived. Please contact administration.';
            } elseif (empty($driver['security_question_id'])) {
                $error = 'No security question set up for this account. Please contact administration to reset your password.';
            } else {
                // Check cooldown
                $cooldown = driver_check_recovery_cooldown($driverId);
                if ($cooldown !== null) {
                    $error = 'Too many failed attempts. ' . driver_get_cooldown_message($cooldown);
                } else {
                    // Store driver ID in session and proceed to step 2
                    $_SESSION['forgot_password_driver_id'] = $driverId;
                    header('Location: driver-forgot-password.php?step=answer_question');
                    exit;
                }
            }
        }
    }
}

// ============================================================================
// STEP 2: Answer Security Question
// ============================================================================
elseif ($step === 'answer_question') {
    // Verify driver ID is in session
    if (empty($_SESSION['forgot_password_driver_id'])) {
        header('Location: driver-forgot-password.php?step=enter_id');
        exit;
    }
    
    $driverId = $_SESSION['forgot_password_driver_id'];
    
    // Check cooldown again
    $cooldown = driver_check_recovery_cooldown($driverId);
    if ($cooldown !== null) {
        $error = 'Too many failed attempts. ' . driver_get_cooldown_message($cooldown);
        // Clear session and go back to step 1
        unset($_SESSION['forgot_password_driver_id']);
        $step = 'enter_id';
    } else {
        // Get security question
        $questionData = driver_get_security_question($driverId);
        
        if (!$questionData) {
            $error = 'Security question not found. Please contact administration.';
            unset($_SESSION['forgot_password_driver_id']);
            $step = 'enter_id';
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $answer = trim($_POST['answer'] ?? '');
                
                if ($answer === '') {
                    $error = 'Please provide your answer.';
                } else {
                    // Verify answer
                    if (driver_verify_security_answer($driverId, $answer)) {
                        // Correct answer! Proceed to step 3
                        $_SESSION['forgot_password_verified'] = true;
                        header('Location: driver-forgot-password.php?step=reset_password');
                        exit;
                    } else {
                        // Wrong answer
                        $pdo = get_pdo();
                        $stmt = $pdo->prepare('SELECT failed_recovery_attempts FROM drivers WHERE driver_id = :id');
                        $stmt->execute(['id' => $driverId]);
                        $attempts = (int) $stmt->fetchColumn();
                        
                        if ($attempts >= 5) {
                            $error = 'Incorrect answer. Too many failed attempts. Please try again in 1 hour.';
                            unset($_SESSION['forgot_password_driver_id']);
                            $step = 'enter_id';
                        } elseif ($attempts >= 3) {
                            $error = 'Incorrect answer. Too many failed attempts. Please try again in 15 minutes.';
                            unset($_SESSION['forgot_password_driver_id']);
                            $step = 'enter_id';
                        } else {
                            $remaining = 3 - $attempts;
                            $error = "Incorrect answer. You have {$remaining} attempt" . ($remaining !== 1 ? 's' : '') . " remaining.";
                        }
                    }
                }
            }
        }
    }
}

// ============================================================================
// STEP 3: Reset Password
// ============================================================================
elseif ($step === 'reset_password') {
    // Verify driver ID and verification flag are in session
    if (empty($_SESSION['forgot_password_driver_id']) || empty($_SESSION['forgot_password_verified'])) {
        header('Location: driver-forgot-password.php?step=enter_id');
        exit;
    }
    
    $driverId = $_SESSION['forgot_password_driver_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword === '' || $confirmPassword === '') {
            $error = 'Please enter and confirm your new password.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match. Please try again.';
        } else {
            // Reset password
            if (driver_reset_password($driverId, $newPassword)) {
                // Clear session data
                unset($_SESSION['forgot_password_driver_id']);
                unset($_SESSION['forgot_password_verified']);
                
                // Redirect to login with success message
                header('Location: driver-login.php?reset=success');
                exit;
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        }
    }
}

// Invalid step
else {
    header('Location: driver-forgot-password.php?step=enter_id');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | BOFETODA</title>
<style><?php echo APP_CSS; ?></style>
<style>
.forgot-container {
    max-width: 540px;
    margin: 0 auto;
    padding: 20px;
}

.forgot-card {
    background: var(--color-white);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-card);
    padding: 32px 24px;
    margin-top: 20px;
}

.forgot-card h2 {
    font-size: 22px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 8px;
    color: var(--color-black);
}

.forgot-card .sub {
    font-size: 13px;
    text-align: center;
    color: var(--color-text-muted);
    line-height: 1.5;
    margin-bottom: 24px;
}

.steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 28px;
}

.step-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--color-border);
    transition: background 0.2s ease;
}

.step-indicator.active {
    background: var(--color-primary);
    width: 28px;
    border-radius: 5px;
}

.step-indicator.completed {
    background: var(--color-success);
}

.question-box {
    background: #f9fafb;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 20px;
    font-size: 14px;
    color: var(--color-text);
    line-height: 1.6;
}

.question-box strong {
    display: block;
    margin-bottom: 8px;
    color: var(--color-black);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group {
    margin-bottom: 18px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 8px;
}

.required {
    color: var(--color-danger);
}

.form-control {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-family: inherit;
    background: var(--color-white);
    color: var(--color-text);
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
}

.hint {
    font-size: 12px;
    color: var(--color-text-muted);
    margin-top: 6px;
    display: block;
}

.form-error {
    background: #fee;
    border: 1px solid #fcc;
    border-radius: var(--radius-sm);
    padding: 10px 12px;
    margin-bottom: 18px;
    font-size: 13px;
    color: var(--color-danger);
}

.password-requirements {
    font-size: 12px;
    color: var(--color-text-muted);
    margin-top: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: var(--radius-sm);
    line-height: 1.6;
}

.password-requirements strong {
    display: block;
    margin-bottom: 6px;
    color: var(--color-text);
}

.btn {
    display: block;
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: var(--radius-lg);
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s ease;
    margin-top: 8px;
}

.btn-primary {
    background: var(--color-primary);
    color: var(--color-black);
}

.btn-primary:active {
    opacity: 0.8;
}

.back-link {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--color-border-light);
}

.back-link a {
    color: var(--color-text-muted);
    font-size: 13px;
    text-decoration: none;
}

.back-link a:active {
    opacity: 0.7;
}
</style>
</head>
<body>

<div class="app-shell no-scroll-pad">
    <div class="header-bar">
        <div class="header-bar__meta">&copy; <?php echo date('Y'); ?> Bocaue TODA Inc.</div>
    </div>

    <div class="forgot-container">
        <div class="forgot-card">
            <h2>Forgot Password</h2>
            <p class="sub">Follow the steps below to reset your password</p>
            
            <!-- Step Indicators -->
            <div class="steps">
                <div class="step-indicator <?php echo $step === 'enter_id' ? 'active' : 'completed'; ?>"></div>
                <div class="step-indicator <?php echo $step === 'answer_question' ? 'active' : ($step === 'reset_password' ? 'completed' : ''); ?>"></div>
                <div class="step-indicator <?php echo $step === 'reset_password' ? 'active' : ''; ?>"></div>
            </div>

            <?php if ($step === 'enter_id'): ?>
                <!-- STEP 1: Enter Driver ID -->
                <form method="POST" action="driver-forgot-password.php?step=enter_id" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="driver_id">Driver ID <span class="required">*</span></label>
                        <input type="text" id="driver_id" name="driver_id" class="form-control" placeholder="DRV-BG0001" autocomplete="username" value="<?php echo h($_POST['driver_id'] ?? ''); ?>" autofocus>
                        <span class="hint">Enter your Driver ID to begin the password reset process.</span>
                    </div>

                    <?php if ($error): ?>
                        <div class="form-error"><?php echo h($error); ?></div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Continue</button>
                </form>

            <?php elseif ($step === 'answer_question'): ?>
                <!-- STEP 2: Answer Security Question -->
                <div class="question-box">
                    <strong>Your Security Question:</strong>
                    <?php echo h($questionData['question']); ?>
                </div>

                <form method="POST" action="driver-forgot-password.php?step=answer_question" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="answer">Your Answer <span class="required">*</span></label>
                        <input type="text" id="answer" name="answer" class="form-control" placeholder="Enter your answer" autocomplete="off" autofocus>
                        <span class="hint">Answer is case-insensitive.</span>
                    </div>

                    <?php if ($error): ?>
                        <div class="form-error"><?php echo h($error); ?></div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Verify Answer</button>
                </form>

            <?php elseif ($step === 'reset_password'): ?>
                <!-- STEP 3: Reset Password -->
                <form method="POST" action="driver-forgot-password.php?step=reset_password" novalidate id="resetForm">
                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password <span class="required">*</span></label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password" autocomplete="new-password" minlength="8" autofocus>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" autocomplete="new-password" minlength="8">
                    </div>

                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        • Must be at least 8 characters long<br>
                        • Should be unique and not easily guessable
                    </div>

                    <?php if ($error): ?>
                        <div class="form-error"><?php echo h($error); ?></div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>

                <script>
                    document.getElementById('resetForm').addEventListener('submit', function(e) {
                        var newPass = document.getElementById('new_password').value;
                        var confirmPass = document.getElementById('confirm_password').value;
                        
                        if (newPass.length < 8) {
                            e.preventDefault();
                            alert('Password must be at least 8 characters long.');
                            return;
                        }
                        
                        if (newPass !== confirmPass) {
                            e.preventDefault();
                            alert('Passwords do not match. Please try again.');
                            return;
                        }
                    });
                </script>
            <?php endif; ?>

            <div class="back-link">
                <a href="driver-login.php">&larr; Back to Login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
