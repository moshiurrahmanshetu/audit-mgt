<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect based on authentication status
if (isLoggedIn()) {
    redirect('/dashboard.php');
} else {
    redirect('/modules/auth/login.php');
}
