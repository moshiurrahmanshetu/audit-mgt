-- Audit Management CMS - Phase 3: Audit Checklist
-- Database Schema for Checklist Module

USE audit_cms;

-- Create checklist_templates table (master list of questions)
CREATE TABLE checklist_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create audit_checklist table (actual responses per audit)
CREATE TABLE audit_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    audit_id INT NOT NULL,
    question_text TEXT NOT NULL,
    response ENUM('Yes', 'No', 'N/A') DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE,
    INDEX idx_audit_id (audit_id),
    INDEX idx_response (response)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert seed data for checklist templates
INSERT INTO checklist_templates (question_text, is_active) VALUES 
('Required documents available?', 1),
('Company policy followed?', 1),
('Records properly maintained?', 1),
('Required approval available?', 1),
('Process properly followed?', 1);
