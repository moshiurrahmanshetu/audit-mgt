<?php
// Session configuration (must be before session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
}

// Application Constants
define('APP_NAME', 'Audit Management CMS');

// Dynamic BASE_URL - auto-detects project root from filesystem path
if (!defined('BASE_URL')) {
    $projectRoot = dirname(__DIR__); // /config/../ = project root
    $documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $projectRootNormalized = rtrim(str_replace('\\', '/', $projectRoot), '/');
    $basePath = str_replace($documentRoot, '', $projectRootNormalized);
    define('BASE_URL', $basePath); // Will be '' if at domain root, or '/subfolder' if in subdirectory
}

define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('DOCUMENTS_PATH', UPLOAD_PATH . '/documents');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');
