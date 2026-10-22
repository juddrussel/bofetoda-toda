-- Migration: Add General Announcements System
-- Description: Adds general_announcements and announcement_images tables
--              for organizational events, reminders, updates, and news
-- Date: 2026-10-23
-- Safe: Can be run multiple times without errors

USE bofetoda;

-- Create general_announcements table
CREATE TABLE IF NOT EXISTS general_announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    category ENUM('Event', 'Reminder', 'Update', 'News', 'Other') NOT NULL DEFAULT 'Other',
    event_date DATETIME NULL DEFAULT NULL COMMENT 'For event-type announcements',
    event_location VARCHAR(255) NULL DEFAULT NULL COMMENT 'Event location/venue',
    priority ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Soft delete flag',
    created_by VARCHAR(20) NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_gen_ann_officer FOREIGN KEY (created_by)
        REFERENCES officers(officer_id) ON UPDATE CASCADE ON DELETE SET NULL,
    
    INDEX idx_gen_ann_category (category),
    INDEX idx_gen_ann_priority (priority),
    INDEX idx_gen_ann_active (is_active),
    INDEX idx_gen_ann_created (created_at),
    INDEX idx_gen_ann_priority_created (priority, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='General announcements for organizational events, reminders, updates, and news';

-- Create announcement_images table
CREATE TABLE IF NOT EXISTS announcement_images (
    image_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0 COMMENT 'Order for displaying images in gallery',
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_ann_img_announcement FOREIGN KEY (announcement_id)
        REFERENCES general_announcements(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    INDEX idx_ann_img_announcement (announcement_id),
    INDEX idx_ann_img_order (announcement_id, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Images associated with general announcements (supports multiple images per announcement)';

-- Verify the changes
SELECT 'General Announcements table structure:' AS '';
DESCRIBE general_announcements;

SELECT 'Announcement Images table structure:' AS '';
DESCRIBE announcement_images;

-- Sample data for testing (optional - remove in production)
-- INSERT INTO general_announcements (title, message, category, priority, created_by) 
-- VALUES ('System Test', 'This is a test announcement', 'Update', 'normal', NULL);

SELECT CONCAT('Migration completed successfully at ', NOW()) AS status;
