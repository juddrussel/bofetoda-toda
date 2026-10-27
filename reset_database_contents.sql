-- ============================================================================
-- BOFETODA Database Content Reset Script
-- ============================================================================
-- This script removes ALL data from tables while preserving table structures
-- WARNING: This will delete all records but keep the schema intact
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Truncate application-related tables
TRUNCATE TABLE applications;
TRUNCATE TABLE application_documents;
TRUNCATE TABLE notifications;
TRUNCATE TABLE driver_messages;
TRUNCATE TABLE message_attachments;

-- Truncate driver data (but keep table structure)
TRUNCATE TABLE drivers;

-- Truncate officer data (but keep table structure)
TRUNCATE TABLE officers;

-- Truncate announcement tables
TRUNCATE TABLE announcements;
TRUNCATE TABLE announcement_images;

-- Truncate renewal cycles
TRUNCATE TABLE renewal_cycles;

-- Truncate access logs and audit trails
TRUNCATE TABLE access_logs;

-- Truncate backup logs if exists
TRUNCATE TABLE backup_logs;

-- Truncate any other system tables
-- Add more TRUNCATE statements here for any additional tables you have

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Optional: Insert default admin account
-- ============================================================================
-- Uncomment the lines below if you want to create a default admin account
-- Password: 'admin123' (hashed with password_hash)

/*
INSERT INTO officers (officer_id, name, role, password_hash, created_at) 
VALUES (
    'ADMIN-001',
    'System Administrator',
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW()
);
*/

-- ============================================================================
-- Verification Queries (run these to check if reset was successful)
-- ============================================================================
-- SELECT COUNT(*) as drivers_count FROM drivers;
-- SELECT COUNT(*) as officers_count FROM officers;
-- SELECT COUNT(*) as applications_count FROM applications;
-- SELECT COUNT(*) as messages_count FROM driver_messages;
-- SELECT COUNT(*) as announcements_count FROM announcements;

SELECT 'Database contents have been reset successfully!' as status;
