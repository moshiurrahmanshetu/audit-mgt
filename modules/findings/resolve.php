<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

$finding_id = intval($_GET['id'] ?? 0);
$error = '';

// Handle GET validation and redirects FIRST
if ($finding_id <= 0) {
    setFlashMessage('Invalid finding ID.', 'danger');
    redirect('/modules/findings/list.php');
}

$user = getCurrentUser();

try {
    // Get finding data
    $stmt = $pdo->prepare("SELECT f.*, a.audit_code, a.title as audit_title, u.full_name as responsible_name 
                          FROM findings f 
                          LEFT JOIN audits a ON f.audit_id = a.id 
                          LEFT JOIN users u ON f.responsible_user_id = u.id 
                          WHERE f.id = ?");
    $stmt->execute([$finding_id]);
    $finding = $stmt->fetch();
    
    if (!$finding) {
        setFlashMessage('Finding not found.', 'danger');
        redirect('/modules/findings/list.php');
    }
    
    // Check access: Only responsible person or Admin can resolve
    if ($user['role'] !== 'Admin' && $finding['responsible_user_id'] != $user['id']) {
        setFlashMessage('You do not have permission to resolve this finding.', 'danger');
        redirect('/modules/findings/view.php?id=' . $finding_id);
    }
    
    // Check if already resolved or closed
    if ($finding['status'] !== 'Open') {
        setFlashMessage('This finding has already been resolved or closed.', 'danger');
        redirect('/modules/findings/view.php?id=' . $finding_id);
    }
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching finding data: ' . $e->getMessage(), 'danger');
    redirect('/modules/findings/list.php');
}

// Handle POST logic FIRST, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resolution_note = sanitize($_POST['resolution_note'] ?? '');
    
    // Validation
    if (empty($resolution_note)) {
        $error = 'Resolution note is required.';
    } else {
        try {
            // Update finding status to Resolved
            $stmt = $pdo->prepare("UPDATE findings SET status = 'Resolved', resolution_note = ? WHERE id = ?");
            $stmt->execute([$resolution_note, $finding_id]);
            
            setFlashMessage('Finding resolved successfully!', 'success');
            redirect('/modules/findings/view.php?id=' . $finding_id);
        } catch (PDOException $e) {
            $error = 'Error resolving finding: ' . $e->getMessage();
        }
    }
}

// Only AFTER all possible redirects, include header and render HTML
$pageTitle = 'Resolve Finding';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-check-circle me-2"></i>Resolve Finding</h2>
    <a href="<?php echo BASE_URL; ?>/modules/findings/view.php?id=<?php echo $finding_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Finding
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Finding:</strong> <?php echo htmlspecialchars($finding['finding_title']); ?><br>
                    <strong>Audit:</strong> <?php echo htmlspecialchars($finding['audit_code'] . ' - ' . $finding['audit_title']); ?>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="resolution_note" class="form-label">Resolution Note <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="resolution_note" name="resolution_note" rows="5" required placeholder="Describe how this finding was resolved..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> Once resolved, the finding can be closed by the creator or an Admin.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-2"></i>Resolve Finding
                        </button>
                        <a href="<?php echo BASE_URL; ?>/modules/findings/view.php?id=<?php echo $finding_id; ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
