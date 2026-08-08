-- Audit Management CMS - Phase 7: Dashboard & Activity Log
-- Database Schema for Activity Log

USE audit_cms;

-- Create activity_log table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    reference_id INT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
