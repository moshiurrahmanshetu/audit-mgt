<?php
// Start output buffering as safety net to prevent "headers already sent" errors
ob_start();

// Load required files first (must be before using any functions)
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-require authentication for all pages that include this file
if (!isLoggedIn()) {
    setFlashMessage('Please login to access this page.', 'danger');
    redirect('/modules/auth/login.php');
}

// Validate that the session's user_id still exists and is active (prevents stale sessions after DB resets)
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $userExists = $stmt->fetch();
    
    if (!$userExists) {
        // User no longer exists or is inactive - force logout
        session_unset();
        session_destroy();
        setFlashMessage('Your session has expired. Please login again.', 'warning');
        redirect('/modules/auth/login.php');
    }
}
