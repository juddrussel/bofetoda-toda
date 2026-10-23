-- Driver-to-Officer Messaging System Database Schema
-- Created: October 23, 2026
-- Description: Tables for helpdesk-style messaging between drivers and officers

-- Table 1: driver_messages
-- Stores all messages in driver-officer conversations
CREATE TABLE IF NOT EXISTS driver_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id VARCHAR(20) NOT NULL,
    sender_type ENUM('driver', 'officer') NOT NULL,
    sender_id INT NOT NULL COMMENT 'driver_id as INT or officer_id',
    message_text TEXT NOT NULL,
    has_attachments BOOLEAN DEFAULT FALSE,
    is_read BOOLEAN DEFAULT FALSE COMMENT 'Whether officer has read this message',
    has_reply BOOLEAN DEFAULT FALSE COMMENT 'Whether driver message has officer reply',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_driver_id (driver_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read),
    INDEX idx_sender_type (sender_type),
    INDEX idx_driver_unread (driver_id, is_read),
    
    FOREIGN KEY (driver_id) REFERENCES drivers(driver_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table 2: message_attachments
-- Stores file attachments for messages
CREATE TABLE IF NOT EXISTS message_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL COMMENT 'R2 key or local path',
    file_size INT NOT NULL COMMENT 'File size in bytes',
    file_type VARCHAR(100) NOT NULL COMMENT 'MIME type',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_message_id (message_id),
    
    FOREIGN KEY (message_id) REFERENCES driver_messages(message_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Success message
SELECT 'Messaging tables created successfully!' AS status;
