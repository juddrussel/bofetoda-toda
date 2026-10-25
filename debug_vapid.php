<?php
/**
 * Debug script to check VAPID configuration
 * DELETE THIS FILE AFTER DEBUGGING
 */

require_once __DIR__ . '/Admin_BOFETODA/config.php';

header('Content-Type: text/plain');

echo "=== VAPID Configuration Debug ===\n\n";

echo "Environment Variables:\n";
echo "VAPID_PUBLIC_KEY exists: " . (getenv('VAPID_PUBLIC_KEY') ? 'YES' : 'NO') . "\n";
echo "VAPID_PRIVATE_KEY exists: " . (getenv('VAPID_PRIVATE_KEY') ? 'YES' : 'NO') . "\n";
echo "VAPID_SUBJECT exists: " . (getenv('VAPID_SUBJECT') ? 'YES' : 'NO') . "\n";
echo "\n";

if (getenv('VAPID_PUBLIC_KEY')) {
    echo "VAPID_PUBLIC_KEY (first 20 chars): " . substr(getenv('VAPID_PUBLIC_KEY'), 0, 20) . "...\n";
}
if (getenv('VAPID_PRIVATE_KEY')) {
    echo "VAPID_PRIVATE_KEY (first 10 chars): " . substr(getenv('VAPID_PRIVATE_KEY'), 0, 10) . "...\n";
}
if (getenv('VAPID_SUBJECT')) {
    echo "VAPID_SUBJECT: " . getenv('VAPID_SUBJECT') . "\n";
}
echo "\n";

echo "get_vapid_config() result:\n";
$config = get_vapid_config();
if ($config) {
    echo "SUCCESS - Config returned:\n";
    echo "  publicKey (first 20 chars): " . substr($config['publicKey'], 0, 20) . "...\n";
    echo "  privateKey (first 10 chars): " . substr($config['privateKey'], 0, 10) . "...\n";
    echo "  subject: " . $config['subject'] . "\n";
} else {
    echo "FAILED - Config is NULL\n";
    echo "Check error_log for messages\n";
}
echo "\n";

echo "is_push_enabled(): " . (is_push_enabled() ? 'TRUE' : 'FALSE') . "\n";
echo "\n";

echo "All environment variables:\n";
foreach (getenv() as $key => $value) {
    if (strpos($key, 'VAPID') !== false) {
        echo "  $key = " . substr($value, 0, 30) . "...\n";
    }
}
