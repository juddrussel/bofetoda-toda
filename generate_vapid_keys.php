<?php
/**
 * VAPID Key Generator for Web Push Notifications
 * Run this script once to generate VAPID keys
 * 
 * VAPID (Voluntary Application Server Identification) keys are required
 * for web push notifications to identify your application to push services.
 */

// Check if web-push library is available
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Error: Composer dependencies not installed.\n\nPlease run: composer install\n");
}

require_once __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\VAPID;

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n";
echo "<html><head><title>VAPID Key Generator</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
h2 { color: #333; }
.key-box { background: #f5f5f5; border: 2px solid #ddd; border-radius: 5px; padding: 15px; margin: 15px 0; }
.key-label { font-weight: bold; color: #555; margin-bottom: 5px; }
.key-value { font-family: monospace; background: white; padding: 10px; border: 1px solid #ccc; border-radius: 3px; word-break: break-all; font-size: 12px; }
.warning { background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; }
.success { background: #d4edda; border: 2px solid #28a745; padding: 15px; border-radius: 5px; margin: 20px 0; }
code { background: #e9ecef; padding: 2px 5px; border-radius: 3px; }
</style>";
echo "</head><body>\n";

echo "<h2>🔑 VAPID Key Generator</h2>\n";

try {
    // Generate VAPID keys
    $vapidKeys = VAPID::createVapidKeys();
    
    $publicKey = $vapidKeys['publicKey'];
    $privateKey = $vapidKeys['privateKey'];
    
    echo "<div class='success'>\n";
    echo "<strong>✓ VAPID Keys Generated Successfully!</strong>\n";
    echo "</div>\n";
    
    echo "<div class='key-box'>\n";
    echo "<div class='key-label'>PUBLIC KEY (Share with browser):</div>\n";
    echo "<div class='key-value'>" . htmlspecialchars($publicKey) . "</div>\n";
    echo "</div>\n";
    
    echo "<div class='key-box'>\n";
    echo "<div class='key-label'>PRIVATE KEY (Keep secret on server):</div>\n";
    echo "<div class='key-value'>" . htmlspecialchars($privateKey) . "</div>\n";
    echo "</div>\n";
    
    echo "<div class='warning'>\n";
    echo "<strong>⚠️ IMPORTANT - Save These Keys Securely!</strong><br><br>\n";
    echo "These keys will NOT be shown again. Follow these steps:\n";
    echo "<ol>\n";
    echo "<li>Copy both keys to a secure location</li>\n";
    echo "<li>Add them to your <code>.env</code> file or environment variables</li>\n";
    echo "<li>Never commit private keys to version control</li>\n";
    echo "<li>If you lose these keys, you must regenerate and re-subscribe all users</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
    echo "<h3>📝 Configuration Instructions</h3>\n";
    echo "<p><strong>Option 1: Environment Variables (Recommended for Render deployment)</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Go to your Render dashboard → Service Settings → Environment</li>\n";
    echo "<li>Add these variables:</li>\n";
    echo "</ol>\n";
    echo "<div class='key-box'>\n";
    echo "<code>VAPID_PUBLIC_KEY</code><br>\n";
    echo htmlspecialchars($publicKey) . "<br><br>\n";
    echo "<code>VAPID_PRIVATE_KEY</code><br>\n";
    echo htmlspecialchars($privateKey) . "<br><br>\n";
    echo "<code>VAPID_SUBJECT</code><br>\n";
    echo "mailto:admin@bofetoda.com (or your contact email)\n";
    echo "</div>\n";
    
    echo "<p><strong>Option 2: Local .env file (For development)</strong></p>\n";
    echo "<p>Create/update <code>.env</code> file in project root:</p>\n";
    echo "<div class='key-box'>\n";
    echo "<pre>";
    echo "VAPID_PUBLIC_KEY=\"" . htmlspecialchars($publicKey) . "\"\n";
    echo "VAPID_PRIVATE_KEY=\"" . htmlspecialchars($privateKey) . "\"\n";
    echo "VAPID_SUBJECT=\"mailto:admin@bofetoda.com\"\n";
    echo "</pre>";
    echo "</div>\n";
    
    echo "<h3>🔧 Next Steps</h3>\n";
    echo "<ol>\n";
    echo "<li>✓ Configure environment variables (see above)</li>\n";
    echo "<li>Test the notification system</li>\n";
    echo "<li>Delete this generator script for security</li>\n";
    echo "</ol>\n";
    
    echo "<p><a href='Admin_BOFETODA/dashboard.php'>← Back to Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<div class='warning'>\n";
    echo "<strong>✗ Error Generating Keys:</strong><br>\n";
    echo htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";
    
    if (!class_exists('Minishlink\WebPush\VAPID')) {
        echo "<p><strong>The web-push library is not installed.</strong></p>\n";
        echo "<p>Run this command in the project directory:</p>\n";
        echo "<div class='key-box'><code>composer install</code></div>\n";
    }
}

echo "</body></html>";
