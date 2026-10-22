<?php

require_once __DIR__ . '/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine current step
$step = $_GET['step'] ?? 'enter_id';
$error = '';
$info = '';

// ============================================================================
// STEP 1: Enter Officer ID
// ============================================================================
if ($step === 'enter_id') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $officerId = trim($_POST['officer_id'] ?? '');
        
        if ($officerId === '') {
            $error = 'Please enter your Officer ID.';
        } else {
            // Check if officer exists and has security question
            $pdo = get_pdo();
            $stmt = $pdo->prepare(
                'SELECT officer_id, security_question_id, is_active 
                 FROM officers 
                 WHERE officer_id = :id'
            );
            $stmt->execute(['id' => $officerId]);
            $officer = $stmt->fetch();
            
            if (!$officer) {
                $error = 'Officer ID not found. Please check and try again.';
            } elseif ((int) $officer['is_active'] !== 1) {
                $error = 'This account is not active. Please contact administration.';
            } elseif (empty($officer['security_question_id'])) {
                $error = 'No security question set up for this account. Please contact administration to reset your password.';
            } else {
                // Check cooldown
                $cooldown = check_recovery_cooldown($officerId);
                if ($cooldown !== null) {
                    $error = 'Too many failed attempts. ' . get_cooldown_message($cooldown);
                } else {
                    // Store officer ID in session and proceed to step 2
                    $_SESSION['forgot_password_officer_id'] = $officerId;
                    header('Location: forgot_password.php?step=answer_question');
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
    // Verify officer ID is in session
    if (empty($_SESSION['forgot_password_officer_id'])) {
        header('Location: forgot_password.php?step=enter_id');
        exit;
    }
    
    $officerId = $_SESSION['forgot_password_officer_id'];
    
    // Check cooldown again
    $cooldown = check_recovery_cooldown($officerId);
    if ($cooldown !== null) {
        $error = 'Too many failed attempts. ' . get_cooldown_message($cooldown);
        // Clear session and go back to step 1
        unset($_SESSION['forgot_password_officer_id']);
        $step = 'enter_id';
    } else {
        // Get security question
        $questionData = get_security_question($officerId);
        
        if (!$questionData) {
            $error = 'Security question not found. Please contact administration.';
            unset($_SESSION['forgot_password_officer_id']);
            $step = 'enter_id';
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $answer = trim($_POST['answer'] ?? '');
                
                if ($answer === '') {
                    $error = 'Please provide your answer.';
                } else {
                    // Verify answer
                    if (verify_officer_security_answer($officerId, $answer)) {
                        // Correct answer! Proceed to step 3
                        $_SESSION['forgot_password_verified'] = true;
                        header('Location: forgot_password.php?step=reset_password');
                        exit;
                    } else {
                        // Wrong answer
                        $pdo = get_pdo();
                        $stmt = $pdo->prepare('SELECT failed_recovery_attempts FROM officers WHERE officer_id = :id');
                        $stmt->execute(['id' => $officerId]);
                        $attempts = (int) $stmt->fetchColumn();
                        
                        if ($attempts >= 5) {
                            $error = 'Incorrect answer. Too many failed attempts. Please try again in 1 hour.';
                            unset($_SESSION['forgot_password_officer_id']);
                            $step = 'enter_id';
                        } elseif ($attempts >= 3) {
                            $error = 'Incorrect answer. Too many failed attempts. Please try again in 15 minutes.';
                            unset($_SESSION['forgot_password_officer_id']);
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
    // Verify officer ID and verification flag are in session
    if (empty($_SESSION['forgot_password_officer_id']) || empty($_SESSION['forgot_password_verified'])) {
        header('Location: forgot_password.php?step=enter_id');
        exit;
    }
    
    $officerId = $_SESSION['forgot_password_officer_id'];
    
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
            if (reset_officer_password($officerId, $newPassword)) {
                // Clear session data
                unset($_SESSION['forgot_password_officer_id']);
                unset($_SESSION['forgot_password_verified']);
                
                // Redirect to login with success message
                header('Location: ' . BASE_URL . '/login.php?reset=success');
                exit;
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        }
    }
}

// Invalid step
else {
    header('Location: forgot_password.php?step=enter_id');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — Bocaue TODA Inc.</title>
<style>
    :root{
        --navy:#16233d;
        --navy-light:#20325a;
        --gold:#f0a020;
        --bg:#f5f6f8;
        --white:#ffffff;
        --border:#e2e5ea;
        --text:#1f2937;
        --text-muted:#6b7280;
        --red:#d32f2f;
        --red-bg:#fdecea;
        --blue:#1976d2;
        --blue-bg:#e3f2fd;
        --radius:10px;
    }
    *{box-sizing:border-box;margin:0;padding:0;}
    body{
        font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
        background:var(--bg);
        color:var(--text);
        min-height:100vh;
        display:flex;
        flex-direction:column;
    }
    .topbar{
        background:var(--navy);
        color:var(--white);
        padding:14px 32px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        font-size:13px;
        letter-spacing:.5px;
    }
    .topbar .muni{font-weight:600;}
    .topbar .copyright{opacity:.75;font-size:12px;}
    .main{
        flex:1;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:70px 24px;
    }
    .forgot-card{
        background:var(--white);
        border:1px solid var(--border);
        border-radius:var(--radius);
        padding:52px 58px 58px;
        width:100%;
        max-width:540px;
        box-shadow:0 4px 18px rgba(22,35,61,.06);
    }
    .forgot-card h2{
        text-align:center;
        font-size:26px;
        color:var(--navy);
        margin-bottom:6px;
    }
    .forgot-card .sub{
        text-align:center;
        font-size:13px;
        color:var(--text-muted);
        margin-bottom:28px;
        letter-spacing:.3px;
        line-height:1.6;
    }
    .steps{
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        margin-bottom:32px;
    }
    .step-indicator{
        width:10px;
        height:10px;
        border-radius:50%;
        background:var(--border);
        transition:background .2s ease;
    }
    .step-indicator.active{
        background:var(--navy);
        width:28px;
        border-radius:5px;
    }
    .step-indicator.completed{
        background:var(--gold);
    }
    label{
        display:block;
        font-size:13px;
        font-weight:700;
        color:var(--text);
        margin-bottom:7px;
        letter-spacing:.3px;
    }
    .required{
        color:var(--red);
        font-size:14px;
    }
    .field{padding-top:18px; margin-bottom:18px;}
    .input-wrap{
        display:flex;
        align-items:center;
        gap:9px;
        background:#f4f5f7;
        border:1px solid var(--border);
        border-radius:8px;
        padding:13px 14px;
        transition:border-color .15s ease, box-shadow .15s ease;
        position:relative;
    }
    .input-wrap:focus-within{
        border-color:var(--navy);
        box-shadow:0 0 0 3px rgba(22,35,61,.08);
    }
    .input-wrap svg{flex-shrink:0;opacity:.55;width:18px;height:18px;}
    .input-wrap input{
        border:none;
        background:transparent;
        outline:none;
        font-size:15px;
        width:100%;
        color:var(--text);
    }
    .toggle-password{
        background:none;
        border:none;
        cursor:pointer;
        padding:0;
        display:flex;
        align-items:center;
        opacity:.5;
        transition:opacity .2s ease;
        flex-shrink:0;
    }
    .toggle-password:hover{
        opacity:.8;
    }
    .toggle-password svg{
        width:20px;
        height:20px;
    }
    .question-box{
        background:#f4f5f7;
        border:1px solid var(--border);
        border-radius:8px;
        padding:16px 18px;
        margin-bottom:24px;
        font-size:14px;
        color:var(--text);
        line-height:1.6;
    }
    .question-box strong{
        display:block;
        margin-bottom:8px;
        color:var(--navy);
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }
    .hint{
        font-size:12px;
        color:var(--text-muted);
        margin-top:6px;
    }
    .error-msg{
        color:var(--red);
        font-size:13px;
        margin:-8px 0 18px;
        background:var(--red-bg);
        padding:10px 12px;
        border-radius:6px;
    }
    .info-msg{
        color:var(--blue);
        font-size:13px;
        margin:-8px 0 18px;
        background:var(--blue-bg);
        padding:10px 12px;
        border-radius:6px;
    }
    .btn-primary{
        width:100%;
        background:var(--navy);
        color:var(--white);
        border:none;
        padding:15px;
        border-radius:999px;
        font-size:15px;
        font-weight:700;
        cursor:pointer;
        margin-top:8px;
        transition:background .15s ease, transform .1s ease;
    }
    .btn-primary:hover{background:var(--navy-light);}
    .btn-primary:active{transform:scale(.98);}
    .back-link{
        text-align:center;
        margin-top:20px;
        padding-top:20px;
        border-top:1px solid var(--border);
    }
    .back-link a{
        color:var(--text-muted);
        font-size:13px;
        text-decoration:none;
        transition:color .15s ease;
    }
    .back-link a:hover{
        color:var(--navy);
    }
    .password-requirements{
        font-size:12px;
        color:var(--text-muted);
        margin-top:12px;
        padding:12px;
        background:#f9fafb;
        border-radius:6px;
        line-height:1.6;
    }
    .password-requirements strong{
        display:block;
        margin-bottom:6px;
        color:var(--text);
    }
</style>
</head>
<body>

<div class="topbar">
    <span class="muni">MUNICIPALITY OF BOCAUE BULACAN</span>
    <span class="copyright">&copy; <?php echo date('Y'); ?> Bocaue TODA Inc.</span>
</div>

<div class="main">
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
            <!-- STEP 1: Enter Officer ID -->
            <form method="POST" action="forgot_password.php?step=enter_id" novalidate>
                <div class="field">
                    <label for="officer_id">Officer ID <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>
                        </svg>
                        <input type="text" id="officer_id" name="officer_id" placeholder="OFF-20240001" autocomplete="username" value="<?php echo htmlspecialchars($_POST['officer_id'] ?? ''); ?>" autofocus>
                    </div>
                    <p class="hint">Enter your Officer ID to begin the password reset process.</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <button type="submit" class="btn-primary">Continue</button>
            </form>

        <?php elseif ($step === 'answer_question'): ?>
            <!-- STEP 2: Answer Security Question -->
            <div class="question-box">
                <strong>Your Security Question:</strong>
                <?php echo htmlspecialchars($questionData['question']); ?>
            </div>

            <form method="POST" action="forgot_password.php?step=answer_question" novalidate>
                <div class="field">
                    <label for="answer">Your Answer <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M2 12h20"/>
                        </svg>
                        <input type="text" id="answer" name="answer" placeholder="Enter your answer" autocomplete="off" autofocus>
                    </div>
                    <p class="hint">Answer is case-insensitive.</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <button type="submit" class="btn-primary">Verify Answer</button>
            </form>

        <?php elseif ($step === 'reset_password'): ?>
            <!-- STEP 3: Reset Password -->
            <form method="POST" action="forgot_password.php?step=reset_password" novalidate id="resetForm">
                <div class="field">
                    <label for="new_password">New Password <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="4" y="10" width="16" height="10" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" autocomplete="new-password" minlength="8" autofocus>
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password')" tabindex="-1">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="4" y="10" width="16" height="10" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password" minlength="8">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')" tabindex="-1">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    • Must be at least 8 characters long<br>
                    • Should be unique and not easily guessable
                </div>

                <?php if ($error): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <button type="submit" class="btn-primary">Reset Password</button>
            </form>

            <script>
                // Toggle password visibility
                function togglePassword(inputId) {
                    var input = document.getElementById(inputId);
                    var button = input.parentElement.querySelector('.toggle-password');
                    var eyeIcon = button.querySelector('.eye-icon');
                    var eyeOffIcon = button.querySelector('.eye-off-icon');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        eyeIcon.style.display = 'none';
                        eyeOffIcon.style.display = 'block';
                    } else {
                        input.type = 'password';
                        eyeIcon.style.display = 'block';
                        eyeOffIcon.style.display = 'none';
                    }
                }
                
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
            <a href="<?php echo BASE_URL; ?>/login.php">&larr; Back to Login</a>
        </div>
    </div>
</div>

</body>
</html>
