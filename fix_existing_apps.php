<?php
/**
 * One-time fix: Update existing applications to set announcement_id
 * DELETE THIS FILE AFTER RUNNING ONCE
 */

require_once 'Admin_BOFETODA/db.php';

$pdo = get_pdo();

// Get open announcement
$stmt = $pdo->query("SELECT announcement_id FROM announcements WHERE status = 'OPEN' LIMIT 1");
$openAnn = $stmt->fetch();
$openAnnId = $openAnn ? $openAnn['announcement_id'] : null;

if (!$openAnnId) {
    die("No open announcement found");
}

// Update all applications with NULL announcement_id that are still active
$stmt = $pdo->prepare("
    UPDATE applications 
    SET announcement_id = ? 
    WHERE announcement_id IS NULL 
    AND status IN ('Pending', 'Validated', 'For Approval', 'Approved')
");

$stmt->execute([$openAnnId]);
$updated = $stmt->rowCount();

echo "<h2>Fix Complete</h2>";
echo "<p>Updated $updated applications to use announcement_id = $openAnnId</p>";
echo "<p><strong>Now delete this file (fix_existing_apps.php)</strong></p>";
?>
