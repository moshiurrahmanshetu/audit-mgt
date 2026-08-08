<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

// Require Admin role
requireRole(['Admin']);

$user_id = intval($_GET['id'] ?? 0);

// Handle GET validation and redirect FIRST
if ($user_id <= 0) {
    setFlashMessage('Invalid user ID.', 'danger');
    redirect('/modules/users/list.php');
}

if ($user_id == $_SESSION['user_id']) {
    setFlashMessage('You cannot deactivate your own account.', 'danger');
    redirect('/modules/users/list.php');
}

try {
    // Get current user status
    $stmt = $pdo->prepare("SELECT status, full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlashMessage('User not found.', 'danger');
        redirect('/modules/users/list.php');
    }
    
    // Toggle status (soft delete/deactivate)
    $new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    
    $action = ($new_status === 'inactive') ? 'deactivated' : 'activated';
    setFlashMessage('User "' . htmlspecialchars($user['full_name']) . '" has been ' . $action . '.', 'success');
} catch (PDOException $e) {
    setFlashMessage('Error updating user status: ' . $e->getMessage(), 'danger');
}

redirect('/modules/users/list.php');
