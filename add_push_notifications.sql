-- Migration: Add Web Push Notifications and Reminder System
-- Description: Creates tables for push subscriptions and reminder tracking,
--              enhances notifications table with type and metadata

-- Table 1: push_subscriptions
-- Stores web push subscription details for drivers and officers
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL COMMENT 'Driver ID or Officer ID',
    user_type ENUM('driver', 'officer') NOT NULL COMMENT 'Type of user',
    endpoint TEXT NOT NULL COMMENT 'Push service endpoint URL',
    p256dh_key VARCHAR(255) NOT NULL COMMENT 'Public key for encryption',
    auth_key VARCHAR(255) NOT NULL COMMENT 'Authentication secret',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Last successful push',
    
    INDEX idx_user_lookup (user_id, user_type),
    INDEX idx_subscribed_at (subscribed_at),
    UNIQUE KEY unique_subscription (user_id, user_type, endpoint(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores web push notification subscriptions for drivers and officers';

-- Table 2: reminder_log
-- Tracks sent deadline reminders to prevent duplicate sends
CREATE TABLE IF NOT EXISTS reminder_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT UNSIGNED NOT NULL COMMENT 'Related announcement',
    reminder_type ENUM('7day', '3day', '1day') NOT NULL COMMENT 'Reminder timing',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recipient_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total recipients',
    driver_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Drivers notified',
    officer_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Officers notified',
    
    INDEX idx_announcement (announcement_id),
    INDEX idx_sent_at (sent_at),
    UNIQUE KEY unique_reminder (announcement_id, reminder_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks sent deadline reminders to prevent duplicates';

-- Enhance notifications table with notification_type and metadata
-- Check if columns exist before adding them
SET @dbname = DATABASE();
SET @tablename = 'notifications';

-- Add notification_type column if it doesn't exist
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = @tablename 
    AND COLUMN_NAME = 'notification_type'
);

SET @query = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN notification_type ENUM(''message_reply'', ''status_change'', ''deadline_reminder'', ''announcement'', ''general'') NOT NULL DEFAULT ''general'' COMMENT ''Type of notification'' AFTER message',
    'SELECT ''notification_type column already exists'' AS info'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add metadata column if it doesn't exist
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = @tablename 
    AND COLUMN_NAME = 'metadata'
);

SET @query = IF(@col_exists = 0,
    'ALTER TABLE notifications ADD COLUMN metadata JSON NULL COMMENT ''Additional notification data'' AFTER notification_type',
    'SELECT ''metadata column already exists'' AS info'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on notification_type for faster queries
SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND INDEX_NAME = 'idx_notification_type'
);

SET @query = IF(@index_exists = 0,
    'ALTER TABLE notifications ADD INDEX idx_notification_type (notification_type)',
    'SELECT ''idx_notification_type already exists'' AS info'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Success message
SELECT 'Push notification tables created successfully!' AS status,
       'Tables: push_subscriptions, reminder_log' AS tables_created,
       'Enhanced: notifications (added notification_type, metadata)' AS enhancements;
