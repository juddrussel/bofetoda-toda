<?php
require_once 'Admin_BOFETODA/config.php';
require_once 'Admin_BOFETODA/db.php';

$driver_id = 'DRV-BG0819'; // Renato Reyes who just got a pending app

echo "<h2>Pending Check Debug</h2>";
echo "<pre>";

$pdo = get_pdo();

// Get open announcement
$openAnnStmt = $pdo->query("SELECT announcement_id FROM announcements WHERE status = 'OPEN' LIMIT 1");
$openAnn = $openAnnStmt->fetch();
$openAnnId = $openAnn ? $openAnn['announcement_id'] : null;

echo "Open Announcement ID: $openAnnId\n\n";

// Check what the search query returns
$search = '%BG0819%';
$sql = "
    SELECT 
        d.driver_id,
        d.name,
        a.app_no,
        a.status,
        a.announcement_id,
        COUNT(a.id) AS has_pending_app
    FROM drivers d
    LEFT JOIN barangays b ON b.barangay_id = d.barangay_id
    LEFT JOIN applications a ON a.driver_id = d.driver_id 
        AND a.status IN ('Pending', 'Validated', 'For Approval', 'Approved')
        AND a.announcement_id = ?
    WHERE d.is_archived = 0
    AND (
        d.name LIKE ? 
        OR d.driver_id LIKE ? 
        OR d.body_no LIKE ?
    )
    GROUP BY d.driver_id, d.name, a.app_no, a.status, a.announcement_id
";

echo "SQL Query:\n$sql\n\n";
echo "Parameters: openAnnId=$openAnnId, search=$search\n\n";

$stmt = $pdo->prepare($sql);
$stmt->execute([$openAnnId, $search, $search, $search]);
$results = $stmt->fetchAll();

echo "Query Results:\n";
print_r($results);

// Also check can_submit directly
$canSubmit = can_submit_assisted_renewal($driver_id);
echo "\ncan_submit_assisted_renewal('$driver_id'): " . ($canSubmit ? 'TRUE (should be FALSE!)' : 'FALSE (correct!)') . "\n";

echo "</pre>";
?>
