<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

// Require Admin or Auditor role
requireRole(['Admin', 'Auditor']);

$audit_id = intval($_GET['id'] ?? 0);

// Handle GET validation and redirect FIRST
if ($audit_id <= 0) {
    setFlashMessage('Invalid audit ID.', 'danger');
    redirect('/modules/audits/list.php');
}

$user = getCurrentUser();

try {
    // Get audit data
    $stmt = $pdo->prepare("SELECT * FROM audits WHERE id = ?");
    $stmt->execute([$audit_id]);
    $audit = $stmt->fetch();
    
    if (!$audit) {
        setFlashMessage('Audit not found.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Check access: Admin can delete any, Auditor can only delete assigned audits
    if ($user['role'] === 'Auditor' && $audit['auditor_id'] != $user['id']) {
        setFlashMessage('You can only delete audits assigned to you.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Check if audit has related data (checklist, findings)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_checklist WHERE audit_id = ?");
    $stmt->execute([$audit_id]);
    $checklist_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM findings WHERE audit_id = ?");
    $stmt->execute([$audit_id]);
    $findings_count = $stmt->fetchColumn();
    
    if ($checklist_count > 0 || $findings_count > 0) {
        setFlashMessage('Cannot delete audit: It has checklist responses or findings associated with it. This is a data integrity protection. To remove this audit, the related data must be deleted first (which is handled by CASCADE FK in the database, but this protection prevents accidental data loss).', 'danger');
        redirect('/modules/audits/edit.php?id=' . $audit_id);
    }
    
    // Delete audit
    $stmt = $pdo->prepare("DELETE FROM audits WHERE id = ?");
    $stmt->execute([$audit_id]);
    
    setFlashMessage('Audit deleted successfully.', 'success');
} catch (PDOException $e) {
    setFlashMessage('Error deleting audit: ' . $e->getMessage(), 'danger');
}

redirect('/modules/audits/list.php');
