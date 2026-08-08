<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log user logout activity before destroying session
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'User Logout', 'Auth', null, 'User logged out');
}

// Destroy all session data
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login page
redirect('/modules/auth/login.php');
