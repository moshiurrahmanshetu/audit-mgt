-- Audit Management CMS - Phase 2: Audit Management
-- Database Schema for Audits Module

USE audit_cms;

-- Create audits table
CREATE TABLE audits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_code VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    auditor_id INT DEFAULT NULL,
    created_by INT NOT NULL,
    audit_date DATE NOT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('Planned', 'In Progress', 'Completed') DEFAULT 'Planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auditor_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_audit_code (audit_code),
    INDEX idx_department (department),
    INDEX idx_auditor_id (auditor_id),
    INDEX idx_created_by (created_by),
    INDEX idx_status (status),
    INDEX idx_audit_date (audit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
