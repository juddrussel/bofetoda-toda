-- Driver-to-Officer Messaging System Database Schema
-- Created: October 23, 2026
-- Description: Tables for helpdesk-style messaging between drivers and officers

-- Table 1: driver_messages
-- Stores all messages in driver-officer conversations
DROP TABLE IF EXISTS message_attachments;
DROP TABLE IF EXISTS driver_messages;

CREATE TABLE driver_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id VARCHAR(20) NOT NULL,
    sender_type ENUM('driver', 'officer') NOT NULL,
    sender_id VARCHAR(20) NOT NULL COMMENT 'driver_id or officer_id',
    message_text TEXT NOT NULL,
    has_attachments TINYINT(1) DEFAULT 0,
    is_read TINYINT(1) DEFAULT 0 COMMENT 'Whether officer has read this message',
    has_reply TINYINT(1) DEFAULT 0 COMMENT 'Whether driver message has officer reply',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_driver_messages (driver_id, sent_at),
    INDEX idx_unread_messages (driver_id, is_read, sender_type),
    INDEX idx_unreplied_messages (driver_id, has_reply, sender_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: message_attachments
-- Stores file attachments for messages
CREATE TABLE message_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NULL COMMENT 'Local path if not in R2',
    file_size INT NOT NULL COMMENT 'File size in bytes',
    file_type VARCHAR(100) NOT NULL COMMENT 'MIME type',
    r2_key VARCHAR(500) NULL COMMENT 'R2 storage key',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_message_attachments (message_id),
    
    FOREIGN KEY (message_id) REFERENCES driver_messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Success message
SELECT 'Messaging tables created successfully!' AS status;
