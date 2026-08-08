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
