<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Create Audit';
require_once __DIR__ . '/../../includes/header.php';

// TODO: log activity in Phase 7

// Require Admin or Auditor role
requireRole(['Admin', 'Auditor']);

$error = '';
$success = '';

// Define departments
$departments = ['Finance', 'HR', 'IT', 'Operations', 'Marketing', 'Sales', 'Legal', 'Compliance'];

try {
    // Get active auditors for dropdown
    $stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Auditor' AND u.status = 'active' ORDER BY u.full_name");
    $stmt->execute();
    $auditors = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('Error fetching auditors: ' . $e->getMessage(), 'danger');
    $auditors = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $auditor_id = intval($_POST['auditor_id'] ?? 0);
    $audit_date = $_POST['audit_date'] ?? '';
    $description = sanitize($_POST['description'] ?? '');
    
    // Validation
    if (empty($title) || empty($department) || empty($audit_date)) {
        $error = 'Title, Department, and Audit Date are required.';
    } elseif (!in_array($department, $departments)) {
        $error = 'Please select a valid department.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $audit_date)) {
        $error = 'Please enter a valid audit date (YYYY-MM-DD).';
    } else {
        try {
            // Generate audit code
            $year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM audits WHERE audit_code LIKE ?");
            $stmt->execute(["AUD-$year-%"]);
            $count = $stmt->fetchColumn() + 1;
            $audit_code = sprintf("AUD-%s-%03d", $year, $count);
            
            // Get current user ID
            $user = getCurrentUser();
            $created_by = $user['id'];
            
            // Insert audit
            $stmt = $pdo->prepare("INSERT INTO audits (audit_code, title, department, auditor_id, created_by, audit_date, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Planned')");
            $stmt->execute([$audit_code, $title, $department, $auditor_id ?: null, $created_by, $audit_date, $description ?: null]);
            
            setFlashMessage('Audit created successfully with code: ' . $audit_code, 'success');
            redirect('/modules/audits/list.php');
        } catch (PDOException $e) {
            $error = 'Error creating audit: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-plus-circle me-2"></i>Create New Audit</h2>
    <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Audits
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
                    <div class="mb-3">
                        <label for="title" class="form-label">Audit Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (isset($_POST['department']) && $_POST['department'] === $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="audit_date" class="form-label">Audit Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="audit_date" name="audit_date" value="<?php echo htmlspecialchars($_POST['audit_date'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="auditor_id" class="form-label">Assign Auditor</label>
                        <select class="form-select" id="auditor_id" name="auditor_id">
                            <option value="">Unassigned</option>
                            <?php foreach ($auditors as $auditor): ?>
                            <option value="<?php echo $auditor['id']; ?>" <?php echo (isset($_POST['auditor_id']) && $_POST['auditor_id'] == $auditor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($auditor['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Can be assigned later if needed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <small class="text-muted">Optional: Provide details about the audit scope and objectives</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> Audit code will be auto-generated (format: AUD-YYYY-XXX)
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Create Audit
                        </button>
                        <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
