<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

// Require Admin or Auditor role
requireRole(['Admin', 'Auditor']);

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
    $stmt = $pdo->prepare("SELECT f.*, a.audit_code, a.title as audit_title 
                          FROM findings f 
                          LEFT JOIN audits a ON f.audit_id = a.id 
                          WHERE f.id = ?");
    $stmt->execute([$finding_id]);
    $finding = $stmt->fetch();
    
    if (!$finding) {
        setFlashMessage('Finding not found.', 'danger');
        redirect('/modules/findings/list.php');
    }
    
    // Check access: Admin can close any, Auditor can only close findings they created
    if ($user['role'] === 'Auditor' && $finding['created_by'] != $user['id']) {
        setFlashMessage('You can only close findings you created.', 'danger');
        redirect('/modules/findings/view.php?id=' . $finding_id);
    }
    
    // Check if finding is Resolved (can only close Resolved findings)
    if ($finding['status'] !== 'Resolved') {
        setFlashMessage('You can only close findings that are already Resolved.', 'danger');
        redirect('/modules/findings/view.php?id=' . $finding_id);
    }
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching finding data: ' . $e->getMessage(), 'danger');
    redirect('/modules/findings/list.php');
}

// Handle POST logic FIRST, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';
    
    if ($confirm) {
        try {
            // Update finding status to Closed
            $stmt = $pdo->prepare("UPDATE findings SET status = 'Closed' WHERE id = ?");
            $stmt->execute([$finding_id]);
            
            // Log finding closure activity
            logActivity($_SESSION['user_id'], 'Closed Finding', 'Finding', $finding_id, "Closed finding: {$finding['finding_title']}");
            
            setFlashMessage('Finding closed successfully!', 'success');
            redirect('/modules/findings/view.php?id=' . $finding_id);
        } catch (PDOException $e) {
            $error = 'Error closing finding: ' . $e->getMessage();
        }
    } else {
        setFlashMessage('Finding closure cancelled.', 'info');
        redirect('/modules/findings/view.php?id=' . $finding_id);
    }
}

// Only AFTER all possible redirects, include header and render HTML
$pageTitle = 'Close Finding';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-x-circle me-2"></i>Close Finding</h2>
    <a href="<?php echo BASE_URL; ?>/modules/findings/view.php?id=<?php echo $finding_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Finding
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Are you sure you want to close this finding?</strong>
                </div>
                
                <div class="mb-4">
                    <p><strong>Finding:</strong> <?php echo htmlspecialchars($finding['finding_title']); ?></p>
                    <p><strong>Audit:</strong> <?php echo htmlspecialchars($finding['audit_code'] . ' - ' . $finding['audit_title']); ?></p>
                    <p><strong>Current Status:</strong> <span class="badge badge-active">Resolved</span></p>
                    <p><strong>New Status:</strong> <span class="badge bg-secondary">Closed</span></p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        This action will mark the finding as Closed. Closed findings cannot be reopened. Make sure the resolution is complete before proceeding.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="confirm" value="yes" class="btn btn-danger">
                            <i class="bi bi-x-lg me-2"></i>Yes, Close Finding
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
