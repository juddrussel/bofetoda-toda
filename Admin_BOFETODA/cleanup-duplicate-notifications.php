<?php
/**
 * Cleanup Duplicate Notifications
 * 
 * This web page removes duplicate notification records for the same driver/app_no/title combination,
 * keeping only the most recent one.
 * 
 * Access: Use ?key=cleanup123 in URL
 */

require_once __DIR__ . '/db.php';

// Simple security key check
$requiredKey = 'cleanup123';
if (!isset($_GET['key']) || $_GET['key'] !== $requiredKey) {
    die('Access denied. Please use the correct URL with security key.');
}

$officer = ['name' => 'System Admin'];

$message = '';
$messageType = '';
$deletedCount = 0;

// Process cleanup if confirmed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cleanup'])) {
    try {
        $pdo = get_pdo();
        
        // First, get count of duplicates before deletion
        $countSql = "
            SELECT COUNT(*) as duplicate_count
            FROM notifications n1
            INNER JOIN notifications n2 
            WHERE n1.driver_id = n2.driver_id
              AND COALESCE(n1.app_no, '') = COALESCE(n2.app_no, '')
              AND n1.title = n2.title
              AND n1.notification_id < n2.notification_id
        ";
        $countStmt = $pdo->query($countSql);
        $duplicateCount = $countStmt->fetchColumn();
        
        if ($duplicateCount > 0) {
            // Delete duplicate notifications, keeping the most recent one
            $deleteSql = "
                DELETE n1 FROM notifications n1
                INNER JOIN notifications n2 
                WHERE n1.driver_id = n2.driver_id
                  AND COALESCE(n1.app_no, '') = COALESCE(n2.app_no, '')
                  AND n1.title = n2.title
                  AND n1.notification_id < n2.notification_id
            ";
            
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute();
            
            $deletedCount = $deleteStmt->rowCount();
            $message = "Cleanup complete! Removed {$deletedCount} duplicate notification(s).";
            $messageType = 'success';
        } else {
            $message = "No duplicate notifications found.";
            $messageType = 'info';
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current duplicate count for preview
try {
    $pdo = get_pdo();
    
    // Get ALL recent notifications to debug (last 20)
    $debugSql = "
        SELECT notification_id, driver_id, app_no, title, message, created_at
        FROM notifications
        ORDER BY created_at DESC
        LIMIT 20
    ";
    $debugStmt = $pdo->query($debugSql);
    $allNotifications = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count duplicates: same driver_id, app_no, and title
    $countSql = "
        SELECT COUNT(*) as duplicate_count
        FROM notifications n1
        INNER JOIN notifications n2 
        WHERE n1.driver_id = n2.driver_id
          AND COALESCE(n1.app_no, '') = COALESCE(n2.app_no, '')
          AND n1.title = n2.title
          AND n1.notification_id < n2.notification_id
    ";
    $countStmt = $pdo->query($countSql);
    $currentDuplicates = $countStmt->fetchColumn();
} catch (Exception $e) {
    $currentDuplicates = 0;
    $allNotifications = [];
    $debugError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Duplicate Notifications - BOFETODA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h1 class="text-2xl font-bold text-slate-800 mb-2">Cleanup Duplicate Notifications</h1>
            <p class="text-slate-600">Remove duplicate notification records from the database</p>
            <p class="text-sm text-slate-500 mt-2">Logged in as: <strong><?= htmlspecialchars($officer['name']) ?></strong></p>
        </div>

        <!-- Message Display -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($messageType === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-blue-50 text-blue-800 border border-blue-200') ?>">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <?php if ($messageType === 'success'): ?>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    <?php else: ?>
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    <?php endif; ?>
                </svg>
                <p><?= htmlspecialchars($message) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status Card -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Current Status</h2>
            
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg">
                <div>
                    <p class="text-sm text-slate-600">Duplicate Notifications Found</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1"><?= $currentDuplicates ?></p>
                </div>
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>

            <?php if ($currentDuplicates > 0): ?>
            <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-sm text-amber-800">
                    <strong>Note:</strong> This will remove duplicate notifications for the same driver, application number, and title, keeping only the most recent one.
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Debug: Show all APP-20260007 notifications -->
        <?php if (isset($debugError)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-800"><strong>Debug Error:</strong> <?= htmlspecialchars($debugError) ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($allNotifications)): ?>
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Debug: Recent Notifications (Last 20)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">ID</th>
                            <th class="px-3 py-2 text-left">Driver ID</th>
                            <th class="px-3 py-2 text-left">App No</th>
                            <th class="px-3 py-2 text-left">Title</th>
                            <th class="px-3 py-2 text-left">Message</th>
                            <th class="px-3 py-2 text-left">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allNotifications as $notif): ?>
                        <tr class="border-t border-slate-200">
                            <td class="px-3 py-2"><?= htmlspecialchars($notif['notification_id']) ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars($notif['driver_id']) ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars($notif['app_no'] ?? 'NULL') ?></td>
                            <td class="px-3 py-2"><?= htmlspecialchars($notif['title']) ?></td>
                            <td class="px-3 py-2 text-xs"><?= htmlspecialchars(substr($notif['message'], 0, 50)) ?>...</td>
                            <td class="px-3 py-2 text-xs"><?= htmlspecialchars($notif['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Form -->
        <?php if ($currentDuplicates > 0): ?>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <form method="POST" onsubmit="return confirm('Are you sure you want to remove <?= $currentDuplicates ?> duplicate notification(s)? This action cannot be undone.');">
                <button type="submit" name="confirm_cleanup" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Remove Duplicate Notifications
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center">
            <svg class="w-16 h-16 text-emerald-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-lg font-semibold text-slate-800 mb-2">All Clean!</p>
            <p class="text-slate-600">No duplicate notifications found in the database.</p>
        </div>
        <?php endif; ?>

        <!-- Back Button -->
        <div class="mt-6 text-center">
            <a href="dashboard.php" class="text-slate-600 hover:text-slate-800 font-medium inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
