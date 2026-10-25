-- ============================================================================
-- Assisted Renewal Feature - Database Migration
-- Adds columns to track staff-assisted renewal submissions
-- 
-- IMPORTANT: Backup database before running!
-- Command: mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d_%H%M%S).sql
-- ============================================================================

-- Add columns to applications table
ALTER TABLE applications
ADD COLUMN uploaded_by_officer_id VARCHAR(20) NULL 
    COMMENT 'Officer who submitted on behalf of driver',
ADD COLUMN consent_given BOOLEAN DEFAULT FALSE 
    COMMENT 'Driver consent for staff-assisted submission',
ADD COLUMN is_staff_assisted BOOLEAN DEFAULT FALSE 
    COMMENT 'Flag for staff-assisted renewals',
ADD CONSTRAINT fk_app_uploaded_by_officer 
    FOREIGN KEY (uploaded_by_officer_id) 
    REFERENCES officers(officer_id) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL;

-- Add columns to application_documents table
ALTER TABLE application_documents
ADD COLUMN uploaded_by_officer_id VARCHAR(20) NULL 
    COMMENT 'Officer who uploaded this specific document',
ADD CONSTRAINT fk_doc_uploaded_by_officer 
    FOREIGN KEY (uploaded_by_officer_id) 
    REFERENCES officers(officer_id) 
    ON UPDATE CASCADE 
    ON DELETE SET NULL;

-- Create indexes for performance
CREATE INDEX idx_applications_uploaded_by ON applications(uploaded_by_officer_id);
CREATE INDEX idx_applications_staff_assisted ON applications(is_staff_assisted);
CREATE INDEX idx_documents_uploaded_by ON application_documents(uploaded_by_officer_id);

-- Display success message
SELECT 'Migration completed successfully!' AS status;
