<?php
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$user = getCurrentUser();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h4>
                <p class="card-text text-muted">You are logged in as <strong><?php echo htmlspecialchars($user['role']); ?></strong></p>
                <hr>
                <p class="card-text">
                    This is the Audit Management CMS dashboard. More features and statistics will be added in future phases.
                </p>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Phase 5 Complete:</strong> Documents / Evidence system is now active.
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-clipboard-check me-2"></i>Audit Management</h5>
                        <p class="card-text">Create and manage audit schedules and assignments.</p>
                        <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="btn btn-primary">
                            Manage Audits
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-exclamation-triangle me-2"></i>Findings</h5>
                        <p class="card-text">View and manage audit findings and issues.</p>
                        <a href="<?php echo BASE_URL; ?>/modules/findings/list.php" class="btn btn-primary">
                            View Findings
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-folder me-2"></i>Documents</h5>
                        <p class="card-text">Upload and manage audit evidence documents.</p>
                        <a href="<?php echo BASE_URL; ?>/modules/documents/list.php" class="btn btn-primary">
                            View Documents
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-person me-2"></i>My Profile</h5>
                        <p class="card-text">View and update your profile information.</p>
                        <a href="<?php echo BASE_URL; ?>/modules/users/profile.php" class="btn btn-primary">
                            View Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (hasRole('Admin')): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-list-check me-2"></i>Checklist Templates</h5>
                        <p class="card-text">Manage master checklist questions for audits.</p>
                        <a href="<?php echo BASE_URL; ?>/modules/checklist/manage.php" class="btn btn-primary">
                            Manage Templates
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (hasRole('Admin')): ?>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-people me-2"></i>User Management</h5>
                        <p class="card-text">Manage system users, roles, and permissions.</p>
                        <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="btn btn-primary">
                            Manage Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person-circle me-2"></i>My Account</h6>
            </div>
            <div class="card-body text-center">
                <img src="<?php echo getAvatarUrl($user['avatar']); ?>" alt="Avatar" class="rounded-circle mb-3" width="100" height="100">
                <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>
            <div class="card-footer">
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>/modules/users/profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil me-2"></i>Edit Profile
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/users/change-password.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-key me-2"></i>Change Password
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>System Info</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><strong>Application:</strong> <?php echo APP_NAME; ?></li>
                    <li class="mb-2"><strong>Version:</strong> Phase 5</li>
                    <li class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Active</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
