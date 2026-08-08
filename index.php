<?php
// Load constants first (needed for BASE_URL)
require_once __DIR__ . '/config/constants.php';

// Check if application is installed
$lockFile = __DIR__ . '/config/installed.lock';
if (!file_exists($lockFile)) {
    header('Location: ' . BASE_URL . '/install/index.php');
    exit;
}

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
