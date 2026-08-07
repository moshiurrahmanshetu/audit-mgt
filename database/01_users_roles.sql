-- Audit Management CMS - Phase 1: Users & Roles
-- Database Schema and Seed Data

CREATE DATABASE IF NOT EXISTS audit_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE audit_cms;

-- Drop tables if they exist (for clean re-import)
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- Create roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert seed data for roles
INSERT INTO roles (role_name) VALUES 
('Admin'),
('Auditor'),
('Staff');

-- Insert default admin user
-- Username: admin
-- Password: Admin@123 (hashed with bcrypt)
-- The hash below is for "Admin@123" using password_hash() with default bcrypt
INSERT INTO users (full_name, username, email, password, role_id, status) VALUES 
('System Administrator', 'admin', 'admin@auditcms.local', '$2y$10$lKEoQEG2CGNEMqREvtFnN..Tr9XW04QfOf2FnzySgpeEC1TFRW2je', 1, 'active');
