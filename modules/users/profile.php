<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// STEP A: Always fetch fresh user data first, unconditionally, before anything else
$stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Should never happen, but handle gracefully instead of continuing with broken data
    redirect('/modules/auth/login.php');
}

$error = '';

// STEP B: Handle POST (form submission) — this REASSIGNS $user only with fresh data AFTER a successful save, never with empty/wrong data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $avatar = $_FILES['avatar'] ?? null;
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error = 'Full name and email are required.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error = 'Email already exists for another user.';
            } else {
                // Handle avatar upload
                $avatar_filename = $user['avatar'];
                
                if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if (!in_array($avatar['type'], $allowed_types)) {
                        $error = 'Only JPG, JPEG, and PNG images are allowed.';
                    } elseif ($avatar['size'] > $max_size) {
                        $error = 'File size must be less than 2MB.';
                    } else {
                        $extension = pathinfo($avatar['name'], PATHINFO_EXTENSION);
                        $avatar_filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
                        
                        if (!is_dir(AVATAR_PATH)) {
                            mkdir(AVATAR_PATH, 0755, true);
                        }
                        
                        if (move_uploaded_file($avatar['tmp_name'], AVATAR_PATH . '/' . $avatar_filename)) {
                            // Delete old avatar if exists
                            if ($user['avatar'] && file_exists(AVATAR_PATH . '/' . $user['avatar'])) {
                                unlink(AVATAR_PATH . '/' . $user['avatar']);
                            }
                        } else {
                            $error = 'Failed to upload avatar.';
                            $avatar_filename = $user['avatar'];
                        }
                    }
                }
                
                if (!$error) {
                    // Update user
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, avatar = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $avatar_filename, $_SESSION['user_id']]);
                    
                    // Update session
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['user_avatar'] = $avatar_filename;
                    
                    // After successful save, RE-FRESH data so the page reflects the update
                    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
                    $stmt->execute(['id' => $_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    setFlashMessage('Profile updated successfully!', 'success');
                }
            }
        } catch (PDOException $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person me-2"></i>My Profile</h2>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <img src="<?php echo getAvatarUrl($user['avatar']); ?>" alt="Avatar" class="rounded-circle mb-3" width="150" height="150" id="currentAvatar">
                <h5 class="card-title"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                <p class="card-text text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($user['role_name']); ?></span>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Quick Actions</h6>
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>/modules/users/change-password.php" class="btn btn-outline-primary">
                        <i class="bi bi-key me-2"></i>Change Password
                    </a>
                    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="avatar" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/jpg,image/png">
                        <small class="text-muted">JPG, JPEG, or PNG. Max 2MB.</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
