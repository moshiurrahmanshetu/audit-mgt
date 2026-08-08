<?php
// Load config and functions if not already loaded (auth_check.php includes them)
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/functions.php';
}

// Load database connection
require_once __DIR__ . '/../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fresh DB lookup for current user to prevent stale session data after database resets
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $navStmt = $pdo->prepare("SELECT u.id, u.full_name, u.username, u.email, u.avatar, r.role_name as role 
                              FROM users u 
                              JOIN roles r ON u.role_id = r.id 
                              WHERE u.id = :id AND u.status = 'active'");
    $navStmt->execute(['id' => $_SESSION['user_id']]);
    $currentUser = $navStmt->fetch(PDO::FETCH_ASSOC);
    
    // If user no longer exists or is inactive (e.g., after database reset), force logout
    if (!$currentUser) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/modules/auth/login.php?msg=session_expired');
        exit;
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle ?? '') . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Flash Messages -->
    <?php if ($flash): ?>
    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" style="z-index: 1060; min-width: 300px;" role="alert">
        <?php echo htmlspecialchars($flash['message'] ?? ''); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-3">
                <div class="container-fluid">
                    <button class="btn btn-link" id="sidebarToggle">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <img src="<?php echo getAvatarUrl($currentUser['avatar'] ?? ''); ?>" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                <span class="d-none d-md-inline text-dark">
                                    <?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;"><?php echo htmlspecialchars($currentUser['role'] ?? ''); ?></small>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/users/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/users/change-password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/modules/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div class="container-fluid p-4">
