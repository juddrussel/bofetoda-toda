<?php

require 'driver-database.php';
require 'driver-template.php';

// Require login
if (empty($_SESSION['logged_in']) || empty($_SESSION['driver_id'])) {
    header('Location: driver-login.php');
    exit;
}

$driver = current_driver();
$driverId = $driver['driver_id'] ?? '';

// If security question already set, redirect to home
if (driver_has_security_question($driverId)) {
    header('Location: driver-home.php');
    exit;
}

$error = '';
$showConfirmation = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId = (int) ($_POST['question_id'] ?? 0);
    $answer = trim($_POST['answer'] ?? '');
    
    // Validation
    if ($questionId < 1 || $questionId > 5) {
        $error = 'Please select a security question.';
    } elseif ($answer === '') {
        $error = 'Please provide an answer to your security question.';
    } elseif (mb_strlen($answer) < 2) {
        $error = 'Answer must be at least 2 characters long.';
    } else {
        // Save security question
        if (driver_save_security_question($driverId, $questionId, $answer)) {
            $showConfirmation = true;
        } else {
            $error = 'Failed to save security question. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Security Question | BOFETODA</title>
<style><?php echo APP_CSS; ?></style>
<style>
.setup-container {
    max-width: 540px;
    margin: 0 auto;
    padding: 20px;
}

.setup-card {
    background: var(--color-white);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-card);
    padding: 32px 24px;
    margin-top: 20px;
}

.setup-card h2 {
    font-size: 22px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 8px;
    color: var(--color-black);
}

.setup-card .sub {
    font-size: 13px;
    text-align: center;
    color: var(--color-text-muted);
    line-height: 1.5;
    margin-bottom: 24px;
}

.info-box {
    background: #fffbf0;
    border: 1px solid #f0e6c8;
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #856404;
    line-height: 1.5;
}

.info-box strong {
    display: block;
    margin-bottom: 6px;
    color: #664d03;
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

select.form-control {
    cursor: pointer;
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
}

.btn-primary {
    background: var(--color-primary);
    color: var(--color-black);
}

.btn-primary:active {
    opacity: 0.8;
}

/* Confirmation styles */
.confirmation {
    text-align: center;
    padding: 20px 0;
}

.confirmation-icon {
    width: 64px;
    height: 64px;
    background: #d4edda;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: var(--color-success);
    font-size: 32px;
}

.confirmation h2 {
    color: var(--color-success);
    margin-bottom: 12px;
}

.confirmation p {
    font-size: 14px;
    color: var(--color-text-muted);
    line-height: 1.6;
    margin-bottom: 8px;
}

.redirect-msg {
    font-size: 13px;
    color: var(--color-text-muted);
    font-style: italic;
    margin-top: 20px;
}
</style>
</head>
<body>

<div class="app-shell no-scroll-pad">
    <div class="header-bar">
        <div class="header-bar__meta">&copy; <?php echo date('Y'); ?> Bocaue TODA Inc.</div>
    </div>

    <div class="setup-container">
        <div class="setup-card">
            <?php if ($showConfirmation): ?>
                <!-- Confirmation View -->
                <div class="confirmation">
                    <div class="confirmation-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h2>Security Question Set Successfully!</h2>
                    <p>Your security question has been saved securely.</p>
                    <p>You can use it to recover your password if you forget it in the future.</p>
                    <p class="redirect-msg">Redirecting to home in <span id="countdown">3</span> seconds...</p>
                </div>
                
                <script>
                    var countdown = 3;
                    var countdownEl = document.getElementById('countdown');
                    var timer = setInterval(function() {
                        countdown--;
                        if (countdown <= 0) {
                            clearInterval(timer);
                            window.location.href = 'driver-home.php';
                        } else {
                            countdownEl.textContent = countdown;
                        }
                    }, 1000);
                </script>
            <?php else: ?>
                <!-- Setup Form View -->
                <h2>Setup Security Question</h2>
                <p class="sub">For your account security, please set up a security question. This will help you recover your password if you forget it.</p>

                <div class="info-box">
                    <strong>Important:</strong>
                    You must complete this step to continue. Choose a question and answer that you will remember, as this will be used to verify your identity when resetting your password.
                </div>

                <form method="POST" action="driver-setup-security.php" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="question_id">Security Question <span class="required">*</span></label>
                        <select id="question_id" name="question_id" class="form-control" required>
                            <option value="">— Select a question —</option>
                            <?php foreach (SECURITY_QUESTIONS as $id => $question): ?>
                                <option value="<?php echo $id; ?>" <?php echo (isset($_POST['question_id']) && $_POST['question_id'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($question); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="answer">Your Answer <span class="required">*</span></label>
                        <input type="text" id="answer" name="answer" class="form-control" placeholder="Enter your answer" autocomplete="off" value="<?php echo htmlspecialchars($_POST['answer'] ?? ''); ?>" required>
                        <span class="hint">Answer is case-insensitive. For example, "Manila" and "manila" are treated the same.</span>
                    </div>

                    <?php if ($error): ?>
                        <div class="form-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Save Security Question</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
