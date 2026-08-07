<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['Admin']);

$pageTitle = 'Users';
require_once __DIR__ . '/../../includes/header.php';

try {
    // Get all users with their roles
    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Get all roles for filter dropdown
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('Error fetching users: ' . $e->getMessage(), 'danger');
    $users = [];
    $roles = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people me-2"></i>User Management</h2>
    <a href="<?php echo BASE_URL; ?>/modules/users/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Add New User
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
        <div class="text-center py-5">
            <i class="bi bi-people fs-1 text-muted"></i>
            <p class="text-muted mt-3">No users found. Create your first user!</p>
            <a href="<?php echo BASE_URL; ?>/modules/users/create.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Add User
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo getAvatarUrl($user['avatar']); ?>" alt="Avatar" class="rounded-circle me-2" width="40" height="40">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['role_name']); ?></span></td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($user['created_at']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/modules/users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary btn-action" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="<?php echo BASE_URL; ?>/modules/users/delete.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-danger btn-action btn-delete" title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="bi bi-<?php echo $user['status'] === 'active' ? 'person-x' : 'person-check'; ?>"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
