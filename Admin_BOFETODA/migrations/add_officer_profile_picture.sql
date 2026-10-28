-- Add profile_picture column to officers table
ALTER TABLE officers 
ADD COLUMN profile_picture VARCHAR(255) NULL AFTER initials;
