-- ============================================================================
-- Assisted Renewal Feature - Rollback Script
-- Removes columns added by add_assisted_renewal_columns.sql
-- ============================================================================

-- Remove columns from application_documents table
ALTER TABLE application_documents
DROP FOREIGN KEY fk_doc_uploaded_by_officer,
DROP COLUMN uploaded_by_officer_id;

-- Remove columns from applications table
ALTER TABLE applications
DROP FOREIGN KEY fk_app_uploaded_by_officer,
DROP COLUMN uploaded_by_officer_id,
DROP COLUMN consent_given,
DROP COLUMN is_staff_assisted;

-- Remove indexes
DROP INDEX idx_applications_uploaded_by ON applications;
DROP INDEX idx_applications_staff_assisted ON applications;
DROP INDEX idx_documents_uploaded_by ON application_documents;

-- Display success message
SELECT 'Rollback completed successfully!' AS status;
