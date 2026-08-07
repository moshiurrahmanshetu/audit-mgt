<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Edit Audit';
require_once __DIR__ . '/../../includes/header.php';

// TODO: log activity in Phase 7

// Require Admin or Auditor role
requireRole(['Admin', 'Auditor']);

$audit_id = intval($_GET['id'] ?? 0);
$error = '';

if ($audit_id <= 0) {
    setFlashMessage('Invalid audit ID.', 'danger');
    redirect('/modules/audits/list.php');
}

$user = getCurrentUser();

try {
    // Get audit data
    $stmt = $pdo->prepare("SELECT a.*, u1.full_name as auditor_name, u2.full_name as created_by_name 
                          FROM audits a 
                          LEFT JOIN users u1 ON a.auditor_id = u1.id 
                          LEFT JOIN users u2 ON a.created_by = u2.id 
                          WHERE a.id = ?");
    $stmt->execute([$audit_id]);
    $audit = $stmt->fetch();
    
    if (!$audit) {
        setFlashMessage('Audit not found.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Check access: Admin can edit any, Auditor can only edit assigned audits
    if ($user['role'] === 'Auditor' && $audit['auditor_id'] != $user['id']) {
        setFlashMessage('You can only edit audits assigned to you.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Get active auditors for dropdown
    $stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Auditor' AND u.status = 'active' ORDER BY u.full_name");
    $stmt->execute();
    $auditors = $stmt->fetchAll();
    
    // Define departments
    $departments = ['Finance', 'HR', 'IT', 'Operations', 'Marketing', 'Sales', 'Legal', 'Compliance'];
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching audit data: ' . $e->getMessage(), 'danger');
    redirect('/modules/audits/list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $auditor_id = intval($_POST['auditor_id'] ?? 0);
    $audit_date = $_POST['audit_date'] ?? '';
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'Planned');
    
    // Validation
    if (empty($title) || empty($department) || empty($audit_date)) {
        $error = 'Title, Department, and Audit Date are required.';
    } elseif (!in_array($department, $departments)) {
        $error = 'Please select a valid department.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $audit_date)) {
        $error = 'Please enter a valid audit date (YYYY-MM-DD).';
    } elseif (!in_array($status, ['Planned', 'In Progress', 'Completed'])) {
        $error = 'Please select a valid status.';
    } else {
        try {
            // Update audit
            $stmt = $pdo->prepare("UPDATE audits SET title = ?, department = ?, auditor_id = ?, audit_date = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $department, $auditor_id ?: null, $audit_date, $description ?: null, $status, $audit_id]);
            
            setFlashMessage('Audit updated successfully!', 'success');
            redirect('/modules/audits/list.php');
        } catch (PDOException $e) {
            $error = 'Error updating audit: ' . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil me-2"></i>Edit Audit</h2>
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
                        <label for="audit_code" class="form-label">Audit Code</label>
                        <input type="text" class="form-control" id="audit_code" name="audit_code" value="<?php echo htmlspecialchars($audit['audit_code']); ?>" readonly class="bg-light">
                        <small class="text-muted">Audit code cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Audit Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($audit['title']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="department" name="department" required>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($audit['department'] === $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="audit_date" class="form-label">Audit Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="audit_date" name="audit_date" value="<?php echo htmlspecialchars($audit['audit_date']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="auditor_id" class="form-label">Assign Auditor</label>
                            <select class="form-select" id="auditor_id" name="auditor_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($auditors as $auditor): ?>
                                <option value="<?php echo $auditor['id']; ?>" <?php echo ($audit['auditor_id'] == $auditor['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($auditor['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Planned" <?php echo ($audit['status'] === 'Planned') ? 'selected' : ''; ?>>Planned</option>
                                <option value="In Progress" <?php echo ($audit['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo ($audit['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($audit['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Update Audit
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
