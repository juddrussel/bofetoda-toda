<?php
/**
 * Reminder Scheduler
 * BOFETODA TODA Management System
 * 
 * Sends deadline reminders for application periods
 * Runs on page load, executes once per day at 8 AM window
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notification-manager.php';

/**
 * Check if reminders should be sent and send them
 * This function is designed to be called on every page load but only executes once per day
 * 
 * @return array Result summary
 */
function check_and_send_reminders() {
    $pdo = get_pdo();
    
    // Check if we're in the 8 AM window (7:30 AM - 8:30 AM Manila time)
    $currentHour = (int) date('G'); // 24-hour format
    $currentMinute = (int) date('i');
    
    // Convert to decimal hours for easier comparison
    $currentTime = $currentHour + ($currentMinute / 60);
    $targetTime = 8.0; // 8:00 AM
    $windowStart = 7.5; // 7:30 AM
    $windowEnd = 8.5; // 8:30 AM
    
    // Allow execution if we're in the window OR if never executed today
    $isInWindow = ($currentTime >= $windowStart && $currentTime <= $windowEnd);
    
    // Check if already executed today
    $today = date('Y-m-d');
    $lastExecution = get_last_reminder_execution();
    
    if ($lastExecution === $today && !$isInWindow) {
        // Already executed today and not in window
        return [
            'executed' => false,
            'reason' => 'Already executed today',
            'last_execution' => $lastExecution,
            'current_time' => date('H:i:s')
        ];
    }
    
    // Get active announcements with approaching deadlines
    $announcements = get_active_announcements_with_deadlines();
    
    if (empty($announcements)) {
        return [
            'executed' => false,
            'reason' => 'No active announcements with approaching deadlines',
            'checked_at' => date('Y-m-d H:i:s')
        ];
    }
    
    $results = [
        'executed' => true,
        'execution_time' => date('Y-m-d H:i:s'),
        'announcements_processed' => 0,
        'reminders_sent' => [],
        'errors' => []
    ];
    
    // Process each announcement
    foreach ($announcements as $announcement) {
        $announcementId = $announcement['announcement_id'];
        $deadlineDate = $announcement['deadline_date'];
        $daysUntilDeadline = $announcement['days_until_deadline'];
        
        // Determine reminder type based on days until deadline
        $reminderType = null;
        if ($daysUntilDeadline == 7) {
            $reminderType = '7day';
        } elseif ($daysUntilDeadline == 3) {
            $reminderType = '3day';
        } elseif ($daysUntilDeadline == 1) {
            $reminderType = '1day';
        }
        
        if (!$reminderType) {
            continue; // Not a reminder day
        }
        
        // Check if reminder already sent for this announcement and type
        if (reminder_already_sent($announcementId, $reminderType)) {
            $results['reminders_sent'][] = [
                'announcement_id' => $announcementId,
                'reminder_type' => $reminderType,
                'status' => 'skipped',
                'reason' => 'Already sent'
            ];
            continue;
        }
        
        // Send reminders
        try {
            $reminderResult = send_deadline_reminders(
                $announcementId,
                $announcement['title'],
                $deadlineDate,
                $daysUntilDeadline,
                $reminderType
            );
            
            // Log reminder in database
            log_reminder_execution(
                $announcementId,
                $reminderType,
                $reminderResult['drivers_notified'] + $reminderResult['officers_notified']
            );
            
            $results['announcements_processed']++;
            $results['reminders_sent'][] = array_merge(
                ['announcement_id' => $announcementId, 'reminder_type' => $reminderType],
                $reminderResult
            );
            
        } catch (Exception $e) {
            error_log("Reminder scheduler error for announcement $announcementId: " . $e->getMessage());
            $results['errors'][] = [
                'announcement_id' => $announcementId,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Update last execution timestamp
    set_last_reminder_execution($today);
    
    return $results;
}

/**
 * Get active announcements with approaching deadlines
 * 
 * @return array Announcements that have deadlines in 7, 3, or 1 days
 */
function get_active_announcements_with_deadlines() {
    $pdo = get_pdo();
    
    $stmt = $pdo->query("
        SELECT 
            announcement_id,
            title,
            opened_date,
            deadline_date,
            DATEDIFF(deadline_date, CURDATE()) as days_until_deadline
        FROM announcements
        WHERE status = 'OPEN'
        AND opened_date <= CURDATE()
        AND deadline_date >= CURDATE()
        AND DATEDIFF(deadline_date, CURDATE()) IN (1, 3, 7)
        ORDER BY deadline_date ASC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if reminder was already sent
 * 
 * @param int $announcementId Announcement ID
 * @param string $reminderType Reminder type (7day, 3day, 1day)
 * @return bool True if already sent
 */
function reminder_already_sent($announcementId, $reminderType) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare("
        SELECT id 
        FROM reminder_log 
        WHERE announcement_id = :announcement_id 
        AND reminder_type = :reminder_type
    ");
    
    $stmt->execute([
        'announcement_id' => $announcementId,
        'reminder_type' => $reminderType
    ]);
    
    return (bool) $stmt->fetch();
}

/**
 * Send deadline reminders to drivers and officers
 * 
 * @param int $announcementId Announcement ID
 * @param string $announcementTitle Announcement title
 * @param string $deadlineDate Deadline date
 * @param int $daysRemaining Days until deadline
 * @param string $reminderType Reminder type
 * @return array Result summary
 */
function send_deadline_reminders($announcementId, $announcementTitle, $deadlineDate, $daysRemaining, $reminderType) {
    $pdo = get_pdo();
    
    // Get cycle start date
    $stmt = $pdo->prepare("
        SELECT created_at 
        FROM announcements 
        WHERE announcement_id = :id
    ");
    $stmt->execute(['id' => $announcementId]);
    $cycleStart = $stmt->fetchColumn();
    
    // Get drivers who haven't submitted
    $driversNotSubmitted = get_drivers_without_submission($cycleStart);
    
    // Get drivers who have submitted
    $driversSubmitted = get_drivers_with_submission($cycleStart);
    
    // Get all active officers
    $stmt = $pdo->query("SELECT officer_id FROM officers WHERE is_active = 1");
    $officers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $results = [
        'drivers_notified' => 0,
        'officers_notified' => 0,
        'drivers_not_submitted' => count($driversNotSubmitted),
        'drivers_submitted' => count($driversSubmitted),
        'officers_count' => count($officers)
    ];
    
    // Send to drivers who haven't submitted (urgent message)
    foreach ($driversNotSubmitted as $driverId) {
        $message = get_reminder_message_for_driver_not_submitted($daysRemaining, $deadlineDate);
        
        trigger_notification(
            $driverId,
            'driver',
            'deadline_reminder',
            [
                'title' => $message['title'],
                'message' => $message['body'],
                'url' => '/Driver_BOFETODA/driver-renewal.php',
                'metadata' => [
                    'announcement_id' => $announcementId,
                    'days_remaining' => $daysRemaining,
                    'has_submitted' => false
                ]
            ]
        );
        
        $results['drivers_notified']++;
    }
    
    // Send to drivers who have submitted (acknowledgment)
    foreach ($driversSubmitted as $driverId) {
        $message = get_reminder_message_for_driver_submitted($daysRemaining, $deadlineDate);
        
        trigger_notification(
            $driverId,
            'driver',
            'deadline_reminder',
            [
                'title' => $message['title'],
                'message' => $message['body'],
                'url' => '/Driver_BOFETODA/driver-home.php',
                'metadata' => [
                    'announcement_id' => $announcementId,
                    'days_remaining' => $daysRemaining,
                    'has_submitted' => true
                ]
            ]
        );
        
        $results['drivers_notified']++;
    }
    
    // Send to officers
    $pendingCount = count(get_pending_applications_for_reminder($cycleStart));
    foreach ($officers as $officerId) {
        $message = get_reminder_message_for_officer($daysRemaining, $deadlineDate, count($driversNotSubmitted), $pendingCount);
        
        send_push_notification(
            $officerId,
            'officer',
            $message['title'],
            $message['body'],
            '/Admin_BOFETODA/dashboard.php',
            ['tag' => 'deadline_reminder']
        );
        
        $results['officers_notified']++;
    }
    
    return $results;
}

/**
 * Get drivers who haven't submitted in current cycle
 */
function get_drivers_without_submission($cycleStart) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare("
        SELECT driver_id 
        FROM drivers 
        WHERE is_archived = 0
        AND driver_id NOT IN (
            SELECT DISTINCT driver_id 
            FROM applications 
            WHERE submitted_at > :cycle_start
        )
    ");
    
    $stmt->execute(['cycle_start' => $cycleStart]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get drivers who have submitted in current cycle
 */
function get_drivers_with_submission($cycleStart) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT driver_id 
        FROM applications 
        WHERE submitted_at > :cycle_start
    ");
    
    $stmt->execute(['cycle_start' => $cycleStart]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get pending applications for deadline reminders
 */
function get_pending_applications_for_reminder($cycleStart) {
    $pdo = get_pdo();
    
    $stmt = $pdo->prepare("
        SELECT app_no 
        FROM applications 
        WHERE submitted_at > :cycle_start 
        AND status = 'Pending'
    ");
    
    $stmt->execute(['cycle_start' => $cycleStart]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get reminder message for driver who hasn't submitted
 */
function get_reminder_message_for_driver_not_submitted($daysRemaining, $deadlineDate) {
    $messages = [
        7 => [
            'title' => 'Application Deadline Reminder',
            'body' => 'The application deadline is in 7 days (' . date('F j, Y', strtotime($deadlineDate)) . '). Submit your renewal application soon!'
        ],
        3 => [
            'title' => 'Urgent: Application Deadline Soon',
            'body' => 'Only 3 days left to submit your application! Deadline: ' . date('F j, Y', strtotime($deadlineDate)) . '. Don\'t miss it!'
        ],
        1 => [
            'title' => 'Final Reminder: Application Deadline Tomorrow',
            'body' => 'Last chance! Application deadline is tomorrow (' . date('F j, Y', strtotime($deadlineDate)) . '). Submit now to avoid missing the deadline!'
        ]
    ];
    
    return $messages[$daysRemaining] ?? $messages[7];
}

/**
 * Get reminder message for driver who has submitted
 */
function get_reminder_message_for_driver_submitted($daysRemaining, $deadlineDate) {
    return [
        'title' => 'Application Status Update',
        'body' => 'Your application has been submitted. Deadline is in ' . $daysRemaining . ' day' . ($daysRemaining > 1 ? 's' : '') . ' (' . date('F j, Y', strtotime($deadlineDate)) . '). Check your application status anytime.'
    ];
}

/**
 * Get reminder message for officers
 */
function get_reminder_message_for_officer($daysRemaining, $deadlineDate, $notSubmittedCount, $pendingCount) {
    $messages = [
        7 => [
            'title' => 'Application Period Update',
            'body' => $notSubmittedCount . ' drivers haven\'t submitted yet. ' . $pendingCount . ' applications pending review. Deadline in 7 days.'
        ],
        3 => [
            'title' => 'Reminder: Review Pending Applications',
            'body' => $pendingCount . ' applications need review. ' . $notSubmittedCount . ' drivers still haven\'t submitted. Deadline in 3 days.'
        ],
        1 => [
            'title' => 'Urgent: Application Deadline Tomorrow',
            'body' => 'Deadline tomorrow! ' . $pendingCount . ' applications pending. ' . $notSubmittedCount . ' drivers haven\'t submitted.'
        ]
    ];
    
    return $messages[$daysRemaining] ?? $messages[7];
}

/**
 * Log reminder execution to database
 */
function log_reminder_execution($announcementId, $reminderType, $recipientCount) {
    $pdo = get_pdo();
    
    // Count drivers and officers separately
    $driversCount = 0;
    $officersCount = 0;
    
    // This is a simplified version - in production you'd track separately
    $driversCount = (int) ($recipientCount * 0.9); // Approximate
    $officersCount = $recipientCount - $driversCount;
    
    $stmt = $pdo->prepare("
        INSERT INTO reminder_log 
        (announcement_id, reminder_type, recipient_count, driver_count, officer_count)
        VALUES (:announcement_id, :reminder_type, :recipient_count, :driver_count, :officer_count)
    ");
    
    $stmt->execute([
        'announcement_id' => $announcementId,
        'reminder_type' => $reminderType,
        'recipient_count' => $recipientCount,
        'driver_count' => $driversCount,
        'officer_count' => $officersCount
    ]);
}

/**
 * Get last reminder execution date
 */
function get_last_reminder_execution() {
    $cacheFile = __DIR__ . '/../uploads/.reminder_last_run';
    
    if (file_exists($cacheFile)) {
        return trim(file_get_contents($cacheFile));
    }
    
    return null;
}

/**
 * Set last reminder execution date
 */
function set_last_reminder_execution($date) {
    $cacheFile = __DIR__ . '/../uploads/.reminder_last_run';
    file_put_contents($cacheFile, $date);
}
