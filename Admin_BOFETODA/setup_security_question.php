<?php

require_once __DIR__ . '/config.php';
require_login();

$officer = current_officer();
$officerId = $officer['id'] ?? '';

// If security question already set, redirect to dashboard
if (has_security_question($officerId)) {
    header('Location: ' . BASE_URL . '/dashboard.php');
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
        if (save_security_question($officerId, $questionId, $answer)) {
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
<title>Setup Security Question — Bocaue TODA Inc.</title>
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
        --green:#2e7d32;
        --green-bg:#e8f5e9;
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
    .setup-card{
        background:var(--white);
        border:1px solid var(--border);
        border-radius:var(--radius);
        padding:52px 58px 58px;
        width:100%;
        max-width:580px;
        box-shadow:0 4px 18px rgba(22,35,61,.06);
    }
    .setup-card h2{
        text-align:center;
        font-size:26px;
        color:var(--navy);
        margin-bottom:6px;
    }
    .setup-card .sub{
        text-align:center;
        font-size:13px;
        color:var(--text-muted);
        margin-bottom:28px;
        letter-spacing:.3px;
        line-height:1.6;
    }
    .info-box{
        background:#fffbf0;
        border:1px solid #f0e6c8;
        border-radius:8px;
        padding:16px 18px;
        margin-bottom:28px;
        font-size:13px;
        color:#856404;
        line-height:1.6;
    }
    .info-box strong{
        display:block;
        margin-bottom:6px;
        color:#664d03;
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
    select, input[type="text"]{
        width:100%;
        display:flex;
        align-items:center;
        gap:9px;
        background:#f4f5f7;
        border:1px solid var(--border);
        border-radius:8px;
        padding:13px 14px;
        transition:border-color .15s ease, box-shadow .15s ease;
        font-size:15px;
        font-family:inherit;
        color:var(--text);
    }
    select:focus, input[type="text"]:focus{
        outline:none;
        border-color:var(--navy);
        box-shadow:0 0 0 3px rgba(22,35,61,.08);
        background:#fff;
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
    .btn-setup{
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
    .btn-setup:hover{background:var(--navy-light);}
    .btn-setup:active{transform:scale(.98);}
    
    /* Confirmation styles */
    .confirmation{
        text-align:center;
    }
    .confirmation-icon{
        width:64px;
        height:64px;
        background:var(--green-bg);
        border-radius:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        margin:0 auto 24px;
        color:var(--green);
        font-size:32px;
    }
    .confirmation h2{
        color:var(--green);
        margin-bottom:12px;
    }
    .confirmation p{
        font-size:14px;
        color:var(--text-muted);
        line-height:1.6;
        margin-bottom:8px;
    }
    .redirect-msg{
        font-size:13px;
        color:var(--text-muted);
        font-style:italic;
        margin-top:20px;
    }
</style>
</head>
<body>

<div class="topbar">
    <span class="muni">MUNICIPALITY OF BOCAUE BULACAN</span>
    <span class="copyright">&copy; <?php echo date('Y'); ?> Bocaue TODA Inc.</span>
</div>

<div class="main">
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
                <p class="redirect-msg">Redirecting to dashboard in <span id="countdown">3</span> seconds...</p>
            </div>
            
            <script>
                var countdown = 3;
                var countdownEl = document.getElementById('countdown');
                var timer = setInterval(function() {
                    countdown--;
                    if (countdown <= 0) {
                        clearInterval(timer);
                        window.location.href = '<?php echo BASE_URL; ?>/dashboard.php';
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

            <form method="POST" action="setup_security_question.php" novalidate>
                <div class="field">
                    <label for="question_id">Security Question <span class="required">*</span></label>
                    <select id="question_id" name="question_id" required>
                        <option value="">— Select a question —</option>
                        <?php foreach (SECURITY_QUESTIONS as $id => $question): ?>
                            <option value="<?php echo $id; ?>" <?php echo (isset($_POST['question_id']) && $_POST['question_id'] == $id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($question); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="answer">Your Answer <span class="required">*</span></label>
                    <input type="text" id="answer" name="answer" placeholder="Enter your answer" autocomplete="off" value="<?php echo htmlspecialchars($_POST['answer'] ?? ''); ?>" required>
                    <p class="hint">Answer is case-insensitive. For example, "Manila" and "manila" are treated the same.</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <button type="submit" class="btn-setup">Save Security Question</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
