<?php
// This file is included after header.php, so session and constants are already loaded
// No need to start session here
?>
<div id="sidebar-wrapper">
    <div class="sidebar-heading">
        <i class="bi bi-shield-check me-2"></i>
        <span class="sidebar-text"><?php echo APP_NAME; ?></span>
    </div>
    <div class="list-group list-group-flush">
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i>
            <span class="sidebar-text">Dashboard</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], 'audits') !== false ? 'active' : ''; ?>">
            <i class="bi bi-clipboard-check me-2"></i>
            <span class="sidebar-text">Audits</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/modules/findings/list.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], 'findings') !== false ? 'active' : ''; ?>">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <span class="sidebar-text">Findings</span>
        </a>
        
        <?php if (hasRole('Admin')): ?>
        <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], 'users/list.php') !== false ? 'active' : ''; ?>">
            <i class="bi bi-people me-2"></i>
            <span class="sidebar-text">Users</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/modules/checklist/manage.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], 'checklist/manage.php') !== false ? 'active' : ''; ?>">
            <i class="bi bi-list-check me-2"></i>
            <span class="sidebar-text">Checklist Templates</span>
        </a>
        <?php endif; ?>
        
        <a href="<?php echo BASE_URL; ?>/modules/users/profile.php" class="list-group-item list-group-item-action <?php echo strpos($_SERVER['PHP_SELF'], 'users/profile.php') !== false ? 'active' : ''; ?>">
            <i class="bi bi-person me-2"></i>
            <span class="sidebar-text">Profile</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/modules/auth/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="bi bi-box-arrow-right me-2"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</div>

