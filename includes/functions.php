<?php
// Load constants if not already loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/constants.php';
}

// Helper Functions

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Set flash message
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Get and clear flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Redirect to a URL
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['user_role'] ?? '',
            'avatar' => $_SESSION['user_avatar'] ?? ''
        ];
    }
    return null;
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Check if user has any of the specified roles
function hasAnyRole($roles) {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

// Generate avatar URL
function getAvatarUrl($avatar = null) {
    if ($avatar && file_exists(AVATAR_PATH . '/' . $avatar)) {
        return BASE_URL . '/uploads/avatars/' . $avatar;
    }
    return BASE_URL . '/assets/img/default-avatar.png';
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate password strength
function isValidPassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
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
