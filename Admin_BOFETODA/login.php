<?php

require_once __DIR__ . '/config.php';

if (isset($_GET['logout'])) {
    if (!empty($_SESSION['officer_id'])) {
        try {
            get_pdo()->prepare('INSERT INTO access_logs (officer_id, action, role) VALUES (:oid, :action, :role)')
                ->execute(['oid' => $_SESSION['officer_id'], 'action' => 'Logout', 'role' => $_SESSION['role'] ?? 'unknown']);
        } catch (PDOException $e) {
            // Log error but don't prevent logout
            error_log('Failed to log access: ' . $e->getMessage());
        }
    }
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$error = '';
$successMessage = '';

// Check for password reset success
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $successMessage = 'Password reset successfully! Please log in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $officerId = trim($_POST['officer_id'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if (attempt_officer_login($officerId, $password)) {
        // Check if security question is set up
        if (!has_security_question($officerId)) {
            // Redirect to security question setup (mandatory)
            header('Location: ' . BASE_URL . '/setup_security_question.php');
            exit;
        }
        
        // Security question is set, proceed to dashboard
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    } else {
        $error = 'Invalid Officer ID or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Bocaue TODA Inc. Administration Portal</title>
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
        gap:70px;
        padding:70px 24px;
        flex-wrap:wrap;
    }
    .brand {
        text-align: center;
        max-width: 460px;
        margin-right: 50px;
    }

    .brand .seals {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .brand .seals .seal-img {
        width: 230px;
        height: 230px;
        object-fit: contain;
        margin-right: -15px;
    }

    .brand .seals .seal-img-bofetoda {
        width: 235px;
        height: 235px;
        object-fit: contain;
        margin-left: -15px;
    }
    .brand h1{
        font-size:42px;
        font-weight:800;
        color:var(--navy);
        margin-bottom:12px;
        letter-spacing:.2px;
        white-space:nowrap;
    }
    .brand p{
        font-size:16px;
        font-weight:600;
        color:var(--text-muted);
        text-transform:uppercase;
        letter-spacing:.5px;
        line-height:1.6;
    }
    .login-card{
        background:var(--white);
        border:1px solid var(--border);
        border-radius:var(--radius);
        padding:52px 58px 58px;
        margin-right:-50px;
        width:500px;
        box-shadow:0 4px 18px rgba(22,35,61,.06);
    }
    .login-card h2{
        text-align:center;
        font-size:26px;
        color:var(--navy);
        margin-bottom:6px;
    }
    .login-card .sub{
        text-align:center;
        font-size:13px;
        color:var(--text-muted);
        margin-bottom:28px;
        letter-spacing:.3px;
    }
    label{
        display:block;
        font-size:13px;
        font-weight:700;
        color:var(--text);
        margin-bottom:7px;
        letter-spacing:.3px;
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
    .toggle-pass{
        cursor:pointer;
        opacity:.55;
        display:flex;
        align-items:center;
        background:none;
        border:none;
        padding:0;
    }
    .toggle-pass:hover{opacity:.9;}
    .error-msg{
        color:var(--red);
        font-size:13px;
        margin:-8px 0 18px;
        background:var(--red-bg);
        padding:10px 12px;
        border-radius:6px;
    }
    .success-msg{
        color:var(--green);
        font-size:13px;
        margin:-8px 0 18px;
        background:var(--green-bg);
        padding:10px 12px;
        border-radius:6px;
    }
    .login-card.has-error .input-wrap{border-color:var(--red);}
    .forgot-password-link{
        text-align:center;
        margin-top:16px;
        padding-top:16px;
        border-top:1px solid var(--border);
    }
    .forgot-password-link a{
        color:var(--navy);
        font-size:13px;
        font-weight:600;
        text-decoration:none;
        transition:color .15s ease;
    }
    .forgot-password-link a:hover{
        color:var(--navy-light);
        text-decoration:underline;
    }
    .btn-login{
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
    .btn-login:hover{background:var(--navy-light);}
    .btn-login:active{transform:scale(.98);}
    @media (max-width:1040px){
        .main{gap:36px;padding:44px 20px;}
        .brand{margin-right:0;}
        .login-card{margin-right:0;width:100%;max-width:500px;}
    }
    @media (max-width:720px){
        .brand h1{font-size:32px;}
        .brand .seals .seal-img,
        .seal-img-bofetoda{width:90px;height:90px;}
    }
</style>
</head>
<body>

<div class="topbar">
    <span class="muni">MUNICIPALITY OF BOCAUE BULACAN</span>
    <span class="copyright">&copy; <?php echo date('Y'); ?> Bocaue TODA Inc.</span>
</div>

<div class="main">
    <div class="brand">
        <div class="seals">
            <img src="LOGO_BOCAUE.png" alt="Bayan ng Bocaue Seal" class="seal-img">
            <img src="LOGO_TODA.png" alt="Bocaue Federation of Tricycle Operators and Drivers Association Seal" class="seal-img-bofetoda">
        </div>
        <h1>BOCAUE TODA INC.</h1>
        <p>Tricycle Membership Permit<br>Management System</p>
    </div>

    <div class="login-card <?php echo $error ? 'has-error' : ''; ?>">
        <h2>Administration Portal</h2>
        <p class="sub">PLEASE ENTER YOUR CREDENTIALS BELOW</p>

        <form method="POST" action="login.php" novalidate>
            <div class="field">
                <label for="officer_id">Officer ID</label>
                <div class="input-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
                    <input type="text" id="officer_id" name="officer_id" placeholder="OFF-20240001" autocomplete="username" value="<?php echo htmlspecialchars($_POST['officer_id'] ?? ''); ?>">
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password">
                    <span class="toggle-pass" onclick="togglePassword()" role="button" aria-label="Toggle password visibility">
                        <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($successMessage): ?>
                <div class="success-msg"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>

            <button type="submit" class="btn-login">Login</button>
            
            <div class="forgot-password-link">
                <a href="<?php echo BASE_URL; ?>/forgot_password.php">Forgot Password?</a>
            </div>
            
            <div style="text-align: center; margin-top: 16px;">
                <a href="/" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); text-decoration: none; font-weight: 600; transition: color .15s;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Home
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>
</body>
</html>