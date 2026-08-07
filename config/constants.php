<?php
// Session configuration (must be before session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
}

// Application Constants
define('APP_NAME', 'Audit Management CMS');
define('BASE_URL', '/audit-mgt'); // Project path for XAMPP Apache server
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('AVATAR_PATH', UPLOAD_PATH . '/avatars');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');
