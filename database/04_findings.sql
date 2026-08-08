-- Audit Management CMS - Phase 4: Findings & Issues
-- Database Schema for Findings Module

USE audit_cms;

-- Create findings table
CREATE TABLE findings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    finding_title VARCHAR(255) NOT NULL,
    description TEXT,
    severity ENUM('High', 'Medium', 'Low') NOT NULL,
    responsible_user_id INT DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    status ENUM('Open', 'Resolved', 'Closed') DEFAULT 'Open',
    resolution_note TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_audit_id (audit_id),
    INDEX idx_responsible_user_id (responsible_user_id),
    INDEX idx_created_by (created_by),
    INDEX idx_severity (severity),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
