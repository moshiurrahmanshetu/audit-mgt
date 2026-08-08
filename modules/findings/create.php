<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

// Require Admin or Auditor role
requireRole(['Admin', 'Auditor']);

$audit_id = intval($_GET['audit_id'] ?? 0);
$error = '';

// Handle GET validation and redirects FIRST
if ($audit_id <= 0) {
    setFlashMessage('Invalid audit ID.', 'danger');
    redirect('/modules/audits/list.php');
}

$user = getCurrentUser();

try {
    // Get audit data
    $stmt = $pdo->prepare("SELECT a.*, u.full_name as auditor_name FROM audits a LEFT JOIN users u ON a.auditor_id = u.id WHERE a.id = ?");
    $stmt->execute([$audit_id]);
    $audit = $stmt->fetch();
    
    if (!$audit) {
        setFlashMessage('Audit not found.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Check access: Admin can create for any audit, Auditor only for assigned audits
    if ($user['role'] === 'Auditor' && $audit['auditor_id'] != $user['id']) {
        setFlashMessage('You can only create findings for audits assigned to you.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Get active users for responsible person dropdown
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching audit data: ' . $e->getMessage(), 'danger');
    redirect('/modules/audits/list.php');
}

// Handle POST logic FIRST, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $finding_title = sanitize($_POST['finding_title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $severity = sanitize($_POST['severity'] ?? '');
    $responsible_user_id = intval($_POST['responsible_user_id'] ?? 0);
    $due_date = $_POST['due_date'] ?? '';
    
    // Validation
    if (empty($finding_title) || empty($severity)) {
        $error = 'Finding title and severity are required.';
    } elseif (!in_array($severity, ['High', 'Medium', 'Low'])) {
        $error = 'Please select a valid severity.';
    } elseif (!empty($due_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
        $error = 'Please enter a valid due date (YYYY-MM-DD).';
    } else {
        try {
            // Insert finding
            $stmt = $pdo->prepare("INSERT INTO findings (audit_id, finding_title, description, severity, responsible_user_id, due_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'Open', ?)");
            $stmt->execute([$audit_id, $finding_title, $description ?: null, $severity, $responsible_user_id ?: null, $due_date ?: null, $user['id']]);
            
            setFlashMessage('Finding created successfully!', 'success');
            redirect('/modules/audits/view.php?id=' . $audit_id);
        } catch (PDOException $e) {
            $error = 'Error creating finding: ' . $e->getMessage();
        }
    }
}

// Only AFTER all possible redirects, include header and render HTML
$pageTitle = 'Create Finding';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-exclamation-triangle me-2"></i>Create Finding</h2>
    <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Audit
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Audit:</strong> <?php echo htmlspecialchars($audit['audit_code'] . ' - ' . $audit['title']); ?>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="finding_title" class="form-label">Finding Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="finding_title" name="finding_title" value="<?php echo htmlspecialchars($_POST['finding_title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                            <select class="form-select" id="severity" name="severity" required>
                                <option value="">Select Severity</option>
                                <option value="High" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'High') ? 'selected' : ''; ?>>High</option>
                                <option value="Medium" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                <option value="Low" <?php echo (isset($_POST['severity']) && $_POST['severity'] === 'Low') ? 'selected' : ''; ?>>Low</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="responsible_user_id" class="form-label">Responsible Person</label>
                            <select class="form-select" id="responsible_user_id" name="responsible_user_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo (isset($_POST['responsible_user_id']) && $_POST['responsible_user_id'] == $u['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['full_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Create Finding
                        </button>
                        <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
