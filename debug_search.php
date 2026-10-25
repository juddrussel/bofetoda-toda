<?php
require_once 'Admin_BOFETODA/config.php';
require_once 'Admin_BOFETODA/db.php';

$driver_id = 'DRV-BG0064'; // The one you're testing

echo "<h2>Debug: Driver Eligibility Check</h2>";
echo "<pre>";

$pdo = get_pdo();

// Check open announcement
$stmt = $pdo->query("SELECT announcement_id, title, status FROM announcements WHERE status = 'OPEN'");
$openAnn = $stmt->fetch();
echo "Open Announcement:\n";
print_r($openAnn);

// Check applications for this driver
$stmt = $pdo->prepare("
    SELECT app_no, status, submitted_at, announcement_id
    FROM applications 
    WHERE driver_id = ?
    ORDER BY submitted_at DESC
    LIMIT 5
");
$stmt->execute([$driver_id]);
$apps = $stmt->fetchAll();
echo "\nApplications for $driver_id:\n";
print_r($apps);

// Check what search returns
$results = search_drivers_for_assisted_renewal('BG0064', 10);
echo "\nSearch Results:\n";
print_r($results);

// Check can_submit
$canSubmit = can_submit_assisted_renewal($driver_id);
echo "\ncan_submit_assisted_renewal($driver_id): " . ($canSubmit ? 'TRUE' : 'FALSE') . "\n";

echo "</pre>";
?>
