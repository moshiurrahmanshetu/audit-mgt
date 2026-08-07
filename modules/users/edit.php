<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['Admin']);

$pageTitle = 'Edit User';
require_once __DIR__ . '/../../includes/header.php';

$user_id = intval($_GET['id'] ?? 0);
$error = '';

if ($user_id <= 0) {
    setFlashMessage('Invalid user ID.', 'danger');
    redirect('/modules/users/list.php');
}

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlashMessage('User not found.', 'danger');
        redirect('/modules/users/list.php');
    }
    
    // Get all roles
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('Error fetching user data: ' . $e->getMessage(), 'danger');
    redirect('/modules/users/list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    $reset_password = isset($_POST['reset_password']);
    $new_password = $_POST['new_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error = 'Full name and email are required.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif ($role_id <= 0) {
        $error = 'Please select a valid role.';
    } elseif ($reset_password && !isValidPassword($new_password)) {
        $error = 'New password must be at least 8 characters with uppercase, lowercase, and number.';
    } else {
        try {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email already exists for another user.';
            } else {
                // Update user
                if ($reset_password && !empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role_id = ?, status = ?, password = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $role_id, $status, $hashed_password, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role_id = ?, status = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $role_id, $status, $user_id]);
                }
                
                setFlashMessage('User updated successfully!', 'success');
                redirect('/modules/users/list.php');
            }
        } catch (PDOException $e) {
            $error = 'Error updating user: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil me-2"></i>Edit User</h2>
    <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Users
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly class="bg-light">
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo ($user['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="reset_password" name="reset_password" onchange="document.getElementById('new_password').disabled = !this.checked; document.getElementById('new_password').required = this.checked;">
                            <label class="form-check-label" for="reset_password">
                                Reset Password
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" disabled>
                        <small class="text-muted">Min 8 chars, uppercase, lowercase, number</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Update User
                        </button>
                        <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
