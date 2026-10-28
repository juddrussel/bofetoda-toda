-- Run this SQL to add profile picture support for officers
-- Copy and paste this into your MySQL/phpMyAdmin SQL tab

USE bofetoda;

ALTER TABLE officers 
ADD COLUMN profile_picture VARCHAR(255) NULL AFTER initials;

-- Verify the column was added
DESCRIBE officers;
