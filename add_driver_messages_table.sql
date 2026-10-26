-- Migration: Add driver_messages table for floating chat feature
-- This table stores messages between drivers and admins

CREATE TABLE IF NOT EXISTS driver_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id VARCHAR(20) NOT NULL,
    sender_type ENUM('driver', 'admin') NOT NULL,
    message TEXT NOT NULL,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    has_reply TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'For driver messages: has admin replied?',
    attachment_path VARCHAR(500) NULL,
    INDEX idx_driver_id (driver_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_is_read (is_read),
    INDEX idx_has_reply (has_reply),
    FOREIGN KEY (driver_id) REFERENCES drivers(driver_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add composite index for unread count queries
CREATE INDEX idx_driver_sender_read ON driver_messages(driver_id, sender_type, is_read);
CREATE INDEX idx_driver_sender_reply ON driver_messages(driver_id, sender_type, has_reply);

