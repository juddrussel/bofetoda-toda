-- Migration: Add Security Question Support (SAFE VERSION)
-- Description: Adds security question columns to officers and drivers tables
--              for password recovery functionality
-- Date: 2026-10-22
-- Safe: Can be run multiple times without errors

USE bofetoda;

-- Add security question columns to officers table (only if they don't exist)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'bofetoda' 
               AND TABLE_NAME = 'officers' 
               AND COLUMN_NAME = 'security_question_id');

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE officers 
     ADD COLUMN security_question_id TINYINT UNSIGNED NULL DEFAULT NULL COMMENT ''ID of the chosen security question (1-5)'',
     ADD COLUMN security_answer_hash VARCHAR(255) NULL DEFAULT NULL COMMENT ''Hashed security answer (case-insensitive)'',
     ADD COLUMN failed_recovery_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''Number of failed password recovery attempts'',
     ADD COLUMN recovery_cooldown_until DATETIME NULL DEFAULT NULL COMMENT ''Cooldown expiry timestamp after failed attempts''', 
    'SELECT ''Officers table already has security question columns'' AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add security question columns to drivers table (only if they don't exist)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'bofetoda' 
               AND TABLE_NAME = 'drivers' 
               AND COLUMN_NAME = 'security_question_id');

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE drivers 
     ADD COLUMN security_question_id TINYINT UNSIGNED NULL DEFAULT NULL COMMENT ''ID of the chosen security question (1-5)'',
     ADD COLUMN security_answer_hash VARCHAR(255) NULL DEFAULT NULL COMMENT ''Hashed security answer (case-insensitive)'',
     ADD COLUMN failed_recovery_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''Number of failed password recovery attempts'',
     ADD COLUMN recovery_cooldown_until DATETIME NULL DEFAULT NULL COMMENT ''Cooldown expiry timestamp after failed attempts''', 
    'SELECT ''Drivers table already has security question columns'' AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the changes
SELECT 'Officers table structure:' AS '';
DESCRIBE officers;

SELECT 'Drivers table structure:' AS '';
DESCRIBE drivers;

-- Security Questions Reference:
-- 1. What is your mother's maiden name?
-- 2. What city were you born in?
-- 3. What is the name of your first pet?
-- 4. What was your childhood nickname?
-- 5. What is your favorite color?

-- Rate Limiting Rules:
-- - 3 failed attempts = 15-minute cooldown
-- - 5 failed attempts = 1-hour cooldown
-- - Correct answer resets attempts to 0 and clears cooldown
