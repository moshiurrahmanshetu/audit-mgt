-- Audit Management CMS - Phase 5: Documents / Evidence
-- Database Schema for Documents Module

USE audit_cms;

-- Create documents table
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    description TEXT DEFAULT NULL,
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_audit_id (audit_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_upload_date (upload_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
