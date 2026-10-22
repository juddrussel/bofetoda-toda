<?php
/**
 * BOFETODA - Tricycle Membership Permit Management System
 * Root Entry Point
 * 
 * This file redirects users to the appropriate portal
 */

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOFETODA - Bocaue TODA Inc.</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .logos {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        h1 {
            font-size: 32px;
            color: #16233d;
            margin-bottom: 10px;
            font-weight: 800;
        }
        .subtitle {
            color: #6b7280;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 50px;
        }
        .portals {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
        }
        .portal-card {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 30px 20px;
            text-decoration: none;
            color: #1f2937;
            transition: all 0.3s ease;
        }
        .portal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        .portal-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .portal-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .portal-desc {
            font-size: 13px;
            color: #6b7280;
        }
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 12px;
        }
        @media (max-width: 640px) {
            .portals {
                grid-template-columns: 1fr;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logos">
            <img src="Admin_BOFETODA/LOGO_BOCAUE.png" alt="Bocaue Seal" class="logo" onerror="this.style.display='none'">
            <img src="Admin_BOFETODA/LOGO_TODA.png" alt="BOFETODA Seal" class="logo" onerror="this.style.display='none'">
        </div>
        
        <h1>BOCAUE TODA INC.</h1>
        <p class="subtitle">Tricycle Membership Permit Management System</p>
        
        <div class="portals">
            <a href="Admin_BOFETODA/login.php" class="portal-card">
                <div class="portal-icon">👔</div>
                <div class="portal-title">Admin Portal</div>
                <div class="portal-desc">For Officers & Staff</div>
            </a>
            
            <a href="Driver_BOFETODA/driver-login.php" class="portal-card">
                <div class="portal-icon">🚗</div>
                <div class="portal-title">Driver Portal</div>
                <div class="portal-desc">For TODA Members</div>
            </a>
        </div>
        
        <div class="footer">
            &copy; <?php echo date('Y'); ?> Bocaue TODA Inc. | Municipality of Bocaue, Bulacan
        </div>
    </div>
</body>
</html>
