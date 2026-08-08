-- Audit Management CMS - Phase 6: Audit Review & Report
-- Database Schema Updates for Review Module

USE audit_cms;

-- Add review-related columns to audits table
ALTER TABLE audits ADD COLUMN auditor_comments TEXT DEFAULT NULL AFTER updated_at;
ALTER TABLE audits ADD COLUMN final_remarks TEXT DEFAULT NULL AFTER auditor_comments;
ALTER TABLE audits ADD COLUMN reviewed_by INT(11) NULL AFTER final_remarks;
ALTER TABLE audits ADD COLUMN reviewed_at TIMESTAMP NULL AFTER reviewed_by;

-- Add foreign key for reviewed_by
ALTER TABLE audits ADD FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

