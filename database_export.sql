-- Database Export for Audit Management CMS
-- Generated: 2026-08-08 09:48:08
-- Database: audit_cms

-- Table: activity_log
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_log` VALUES ('1', '1', 'Created User', 'User', '2', 'Created user user1 (atom shetu)', '2026-08-08 11:37:33');
INSERT INTO `activity_log` VALUES ('2', '2', 'User Login', 'Auth', NULL, 'User logged in', '2026-08-08 11:38:08');
INSERT INTO `activity_log` VALUES ('3', '1', 'User Logout', 'Auth', NULL, 'User logged out', '2026-08-08 12:07:06');
INSERT INTO `activity_log` VALUES ('4', '2', 'User Logout', 'Auth', NULL, 'User logged out', '2026-08-08 12:09:43');
INSERT INTO `activity_log` VALUES ('5', '2', 'User Login', 'Auth', NULL, 'User logged in', '2026-08-08 12:10:06');
INSERT INTO `activity_log` VALUES ('6', '1', 'User Login', 'Auth', NULL, 'User logged in', '2026-08-08 12:15:19');

-- Table: audit_checklist
DROP TABLE IF EXISTS `audit_checklist`;
CREATE TABLE `audit_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `response` enum('Yes','No','N/A') DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_id` (`audit_id`),
  KEY `idx_response` (`response`),
  CONSTRAINT `audit_checklist_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table: audits
DROP TABLE IF EXISTS `audits`;
CREATE TABLE `audits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `auditor_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `audit_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Planned','In Progress','Completed') DEFAULT 'Planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `auditor_comments` text DEFAULT NULL,
  `final_remarks` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_code` (`audit_code`),
  KEY `idx_audit_code` (`audit_code`),
  KEY `idx_department` (`department`),
  KEY `idx_auditor_id` (`auditor_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_audit_date` (`audit_date`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `audits_ibfk_1` FOREIGN KEY (`auditor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `audits_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `audits_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table: checklist_templates
DROP TABLE IF EXISTS `checklist_templates`;
CREATE TABLE `checklist_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_text` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `checklist_templates` VALUES ('1', 'Required documents available?', '1', '2026-08-08 10:53:53');
INSERT INTO `checklist_templates` VALUES ('2', 'Company policy followed?', '1', '2026-08-08 10:53:53');
INSERT INTO `checklist_templates` VALUES ('3', 'Records properly maintained?', '1', '2026-08-08 10:53:53');
INSERT INTO `checklist_templates` VALUES ('4', 'Required approval available?', '1', '2026-08-08 10:53:53');
INSERT INTO `checklist_templates` VALUES ('5', 'Process properly followed?', '1', '2026-08-08 10:53:53');

-- Table: documents
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_id` (`audit_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_upload_date` (`upload_date`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table: findings
DROP TABLE IF EXISTS `findings`;
CREATE TABLE `findings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` int(11) NOT NULL,
  `finding_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('High','Medium','Low') NOT NULL,
  `responsible_user_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Open','Resolved','Closed') DEFAULT 'Open',
  `resolution_note` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_id` (`audit_id`),
  KEY `idx_responsible_user_id` (`responsible_user_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`status`),
  CONSTRAINT `findings_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `findings_ibfk_2` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `findings_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table: roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`),
  KEY `idx_role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` VALUES ('1', 'Admin', '2026-08-08 10:53:04');
INSERT INTO `roles` VALUES ('2', 'Auditor', '2026-08-08 10:53:04');
INSERT INTO `roles` VALUES ('3', 'Staff', '2026-08-08 10:53:04');

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES ('1', 'atom shetu', 'admin', 'atomicshetu@gmail.com', '$2y$10$lKEoQEG2CGNEMqREvtFnN..Tr9XW04QfOf2FnzySgpeEC1TFRW2je', '1', 'avatar_1_1786168293.png', 'active', '2026-08-08 10:53:04', '2026-08-08 11:51:33');
INSERT INTO `users` VALUES ('2', 'staff staff', 'user1', 'staff@gmail.com', '$2y$10$mluXb6.uRI26UPs4tVw8m.pesHjyv2/.pKow8S0.14WFvadGgjt8y', '3', 'avatar_2_1786171478.jpg', 'active', '2026-08-08 11:37:33', '2026-08-08 12:44:38');

