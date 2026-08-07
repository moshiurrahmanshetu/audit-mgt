<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require authentication
function requireAuth() {
    if (!isLoggedIn()) {
        setFlashMessage('Please login to access this page.', 'danger');
        redirect('/modules/auth/login.php');
    }
}

// Require specific role
function requireRole($roles) {
    requireAuth();
    
    if (!hasAnyRole((array) $roles)) {
        setFlashMessage('You do not have permission to access this page.', 'danger');
        redirect('/dashboard.php');
    }
}

// Auto-require authentication for all pages that include this file
requireAuth();
