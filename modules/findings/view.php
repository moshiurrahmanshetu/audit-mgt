<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

$finding_id = intval($_GET['id'] ?? 0);

// Handle GET validation and redirects FIRST
if ($finding_id <= 0) {
    setFlashMessage('Invalid finding ID.', 'danger');
    redirect('/modules/findings/list.php');
}

$user = getCurrentUser();

try {
    // Get finding data
    $stmt = $pdo->prepare("SELECT f.*, a.audit_code, a.title as audit_title, u1.full_name as responsible_name, u2.full_name as created_by_name 
                          FROM findings f 
                          LEFT JOIN audits a ON f.audit_id = a.id 
                          LEFT JOIN users u1 ON f.responsible_user_id = u1.id 
                          LEFT JOIN users u2 ON f.created_by = u2.id 
                          WHERE f.id = ?");
    $stmt->execute([$finding_id]);
    $finding = $stmt->fetch();
    
    if (!$finding) {
        setFlashMessage('Finding not found.', 'danger');
        redirect('/modules/findings/list.php');
    }
    
    // Check access: Staff only if they are responsible, Auditor/Admin for assigned audits
    if ($user['role'] === 'Staff' && $finding['responsible_user_id'] != $user['id']) {
        setFlashMessage('You do not have permission to view this finding.', 'danger');
        redirect('/modules/findings/list.php');
    }
    
    if ($user['role'] === 'Auditor') {
        // Auditor can view if they created it, are responsible, or audit is assigned to them
        $stmt = $pdo->prepare("SELECT auditor_id FROM audits WHERE id = ?");
        $stmt->execute([$finding['audit_id']]);
        $audit = $stmt->fetch();
        
        if ($finding['created_by'] != $user['id'] && $finding['responsible_user_id'] != $user['id'] && $audit['auditor_id'] != $user['id']) {
            setFlashMessage('You do not have permission to view this finding.', 'danger');
            redirect('/modules/findings/list.php');
        }
    }
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching finding data: ' . $e->getMessage(), 'danger');
    redirect('/modules/findings/list.php');
}

// Define severity and status colors
$severity_colors = [
    'High' => 'badge-inactive',
    'Medium' => 'bg-warning',
    'Low' => 'badge-active'
];

$status_colors = [
    'Open' => 'badge-inactive',
    'Resolved' => 'badge-active',
    'Closed' => 'bg-secondary'
];

// Only AFTER all possible redirects, include header and render HTML
$pageTitle = 'View Finding';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-eye me-2"></i>View Finding</h2>
    <a href="<?php echo BASE_URL; ?>/modules/findings/list.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Findings
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?php echo htmlspecialchars($finding['finding_title']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Audit:</strong>
                        <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $finding['audit_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($finding['audit_code'] . ' - ' . $finding['audit_title']); ?>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <strong>Severity:</strong>
                        <span class="badge <?php echo $severity_colors[$finding['severity']]; ?>">
                            <?php echo htmlspecialchars($finding['severity']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Status:</strong>
                        <span class="badge <?php echo $status_colors[$finding['status']]; ?>">
                            <?php echo htmlspecialchars($finding['status']); ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <strong>Due Date:</strong>
                        <?php echo $finding['due_date'] ? formatDate($finding['due_date']) : 'Not set'; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Responsible Person:</strong>
                        <?php echo htmlspecialchars($finding['responsible_name'] ?? 'Unassigned'); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Created By:</strong>
                        <?php echo htmlspecialchars($finding['created_by_name']); ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="mb-3">
                    <strong>Description:</strong>
                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($finding['description'] ?? 'No description provided.')); ?></div>
                </div>
                
                <?php if ($finding['resolution_note']): ?>
                <div class="mb-3">
                    <strong>Resolution Note:</strong>
                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($finding['resolution_note'])); ?></div>
                </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="d-flex gap-2">
                    <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
                        <?php if ($user['role'] === 'Admin' || $finding['created_by'] == $user['id']): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/findings/edit.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Edit Finding
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($finding['status'] === 'Open' && ($user['role'] === 'Admin' || $finding['responsible_user_id'] == $user['id'])): ?>
                    <a href="<?php echo BASE_URL; ?>/modules/findings/resolve.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-success">
                        <i class="bi bi-check me-2"></i>Resolve Finding
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($finding['status'] === 'Resolved' && ($user['role'] === 'Admin' || $finding['created_by'] == $user['id'])): ?>
                    <a href="<?php echo BASE_URL; ?>/modules/findings/close.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Close Finding
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Finding Details</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><strong>Created:</strong> <?php echo formatDate($finding['created_at'], 'M d, Y H:i'); ?></li>
                    <li class="mb-2"><strong>Updated:</strong> <?php echo formatDate($finding['updated_at'], 'M d, Y H:i'); ?></li>
                    <li class="mb-0"><strong>ID:</strong> #<?php echo $finding['id']; ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
