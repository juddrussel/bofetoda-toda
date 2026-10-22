-- Migration: Add Security Question Support
-- Description: Adds security question columns to officers and drivers tables
--              for password recovery functionality
-- Date: 2026-10-22

USE bofetoda;

-- Add security question columns to officers table
ALTER TABLE officers 
ADD COLUMN security_question_id TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'ID of the chosen security question (1-5)',
ADD COLUMN security_answer_hash VARCHAR(255) NULL DEFAULT NULL COMMENT 'Hashed security answer (case-insensitive)',
ADD COLUMN failed_recovery_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of failed password recovery attempts',
ADD COLUMN recovery_cooldown_until DATETIME NULL DEFAULT NULL COMMENT 'Cooldown expiry timestamp after failed attempts';

-- Add security question columns to drivers table
ALTER TABLE drivers 
ADD COLUMN security_question_id TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'ID of the chosen security question (1-5)',
ADD COLUMN security_answer_hash VARCHAR(255) NULL DEFAULT NULL COMMENT 'Hashed security answer (case-insensitive)',
ADD COLUMN failed_recovery_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of failed password recovery attempts',
ADD COLUMN recovery_cooldown_until DATETIME NULL DEFAULT NULL COMMENT 'Cooldown expiry timestamp after failed attempts';

-- Verify the changes
DESCRIBE officers;
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
