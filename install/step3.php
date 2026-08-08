<?php
require_once __DIR__ . '/guard.php';

/*
 * CLEAN DATABASE EXPORT REQUIREMENTS FOR MARKETPLACE DISTRIBUTION:
 * 
 * When preparing the official database export file for marketplace buyers,
 * ensure the following structure:
 * 
 * REQUIRED (must exist with seeded data):
 * - roles table: MUST have 3 seeded rows (Admin, Auditor, Staff) - these are required reference data
 * - checklist_templates table: SHOULD have 5 seeded default questions (Phase 3 seed data) - default reference content
 * 
 * OPTIONAL/EMPTY (can be 0 rows for clean template):
 * - users table: CAN be empty (0 rows) - Step 3 handles both empty users table and pre-existing seeded admin
 * - audits table: SHOULD be empty (0 rows) - test data, should not ship to buyers
 * - audit_checklist table: SHOULD be empty (0 rows) - test data, should not ship to buyers
 * - findings table: SHOULD be empty (0 rows) - test data, should not ship to buyers
 * - documents table: SHOULD be empty (0 rows) - test data, should not ship to buyers
 * - activity_log table: SHOULD be empty (0 rows) - test data, should not ship to buyers
 * 
 * Step 3 logic handles two scenarios:
 * 1. Empty users table: INSERTs a new admin user with buyer's credentials
 * 2. Pre-existing seeded admin: UPDATEs that user with buyer's credentials
 * 
 * Both scenarios are valid and expected. The only error case is if the Admin role
 * is missing from the roles table entirely (indicates wrong/corrupted SQL file).
 */

// Check if we have database credentials
if (!isset($_SESSION['install_db'])) {
    header('Location: step1.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        try {
            $creds = $_SESSION['install_db'];
            $dsn = "mysql:host={$creds['host']};port={$creds['port']};dbname={$creds['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $creds['user'], $creds['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Step 1: Verify Admin role exists in roles table
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = 'Admin' LIMIT 1");
            $stmt->execute();
            $adminRole = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$adminRole) {
                throw new Exception('The imported database is missing the required "Admin" role. Please ensure you uploaded the correct database export file.');
            }
            
            $adminRoleId = $adminRole['id'];
            
            // Step 2: Check if username or email already exists (for uniqueness validation)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                $errors[] = 'Username or email already exists in the database. Please choose different credentials.';
            }
            
            if (empty($errors)) {
                // Step 3: Check if an admin user already exists (for UPDATE vs INSERT decision)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE role_id = ? ORDER BY id ASC LIMIT 1");
                $stmt->execute([$adminRoleId]);
                $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                if ($existingAdmin) {
                    // Case 1: Update existing seeded admin user
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, password = ?, status = 'active', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$full_name, $username, $email, $hashedPassword, $existingAdmin['id']]);
                } else {
                    // Case 2: Insert new admin user (clean database with no users)
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role_id, status, created_at, updated_at) 
                                         VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())");
                    $stmt->execute([$full_name, $username, $email, $hashedPassword, $adminRoleId]);
                }
                
                // Store admin info for final step
                $_SESSION['install_admin'] = [
                    'username' => $username,
                    'full_name' => $full_name
                ];
                
                header('Location: finish.php');
                exit;
            }
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Audit Management CMS</title>
    <link rel="stylesheet" href="assets/installer.css">
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🛡️ Audit Management CMS Setup</h1>
            <p>Step 4 of 5: Admin Account</p>
        </div>
        
        <div class="progress-steps">
            <div class="step completed">
                <span class="step-number">✓</span>
                <span>Requirements</span>
            </div>
            <div class="step completed">
                <span class="step-number">✓</span>
                <span>Database</span>
            </div>
            <div class="step completed">
                <span class="step-number">✓</span>
                <span>Import</span>
            </div>
            <div class="step active">
                <span class="step-number">4</span>
                <span>Admin</span>
            </div>
            <div class="step">
                <span class="step-number">5</span>
                <span>Done</span>
            </div>
        </div>
        
        <div class="installer-body">
            <h2 style="margin-bottom: 20px; color: #f1f5f9;">Create Admin Account</h2>
            
            <p style="color: #94a3b8; font-size: 14px; margin-bottom: 24px;">
                This step will create your admin account. If the imported database already contains a default admin user, it will be updated with your credentials. Otherwise, a new admin account will be created.
            </p>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>⚠️ Please fix the following errors:</strong>
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Admin Username *</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    <div class="form-help">This will replace "admin" as your login username</div>
                </div>
                
                <div class="form-group">
                    <label for="email">Admin Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <div class="form-help">Minimum 8 characters</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary btn-block">
                        Complete Installation →
                    </button>
                </div>
            </form>
        </div>
        
        <div class="installer-footer">
            <a href="step2.php" style="color: #64748b; text-decoration: none;">← Back</a>
        </div>
    </div>
</body>
</html>
