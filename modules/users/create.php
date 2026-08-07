<?php
require_once __DIR__ . '/../../includes/auth_check.php';
requireRole(['Admin']);

$pageTitle = 'Create User';
require_once __DIR__ . '/../../includes/header.php';

$error = '';
$success = '';

try {
    // Get all roles
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY role_name");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('Error fetching roles: ' . $e->getMessage(), 'danger');
    $roles = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!isValidPassword($password)) {
        $error = 'Password must be at least 8 characters with uppercase, lowercase, and number.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif ($role_id <= 0) {
        $error = 'Please select a valid role.';
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already exists.';
                } else {
                    // Insert new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role_id, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$full_name, $username, $email, $hashed_password, $role_id, $status]);
                    
                    setFlashMessage('User created successfully!', 'success');
                    redirect('/modules/users/list.php');
                }
            }
        } catch (PDOException $e) {
            $error = 'Error creating user: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-plus me-2"></i>Create New User</h2>
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
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores">
                            <small class="text-muted">Only letters, numbers, and underscores</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Min 8 chars, uppercase, lowercase, number</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Create User
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
