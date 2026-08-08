-- Audit Management CMS - Phase 6: Audit Review & Report
-- Database Schema Updates for Review Module

USE audit_cms;

-- Fix reviewed_by column type to match users.id (INT(11))
ALTER TABLE audits MODIFY COLUMN reviewed_by INT(11) NULL;

