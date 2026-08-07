<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

// Require Admin role only
requireRole(['Admin']);

$audit_id = intval($_GET['id'] ?? 0);

if ($audit_id <= 0) {
    setFlashMessage('Invalid audit ID.', 'danger');
    redirect('/modules/audits/list.php');
}

try {
    // Get current audit status
    $stmt = $pdo->prepare("SELECT audit_code, title, status FROM audits WHERE id = ?");
    $stmt->execute([$audit_id]);
    $audit = $stmt->fetch();
    
    if (!$audit) {
        setFlashMessage('Audit not found.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // For safety, we only allow changing status, not physical deletion
    // This prevents FK constraint violations in future phases
    setFlashMessage('Physical deletion is not allowed to maintain data integrity. Please change the audit status instead.', 'warning');
    redirect('/modules/audits/edit.php?id=' . $audit_id);
    
} catch (PDOException $e) {
    setFlashMessage('Error accessing audit: ' . $e->getMessage(), 'danger');
    redirect('/modules/audits/list.php');
}
