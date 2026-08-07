<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'View Audit';
require_once __DIR__ . '/../../includes/header.php';

// TODO: log activity in Phase 7

$audit_id = intval($_GET['id'] ?? 0);

if ($audit_id <= 0) {
    setFlashMessage('Invalid audit ID.', 'danger');
    redirect('/modules/audits/list.php');
}

$user = getCurrentUser();

try {
    // Get audit data
    $stmt = $pdo->prepare("SELECT a.*, 
                          u1.full_name as auditor_name, 
                          u2.full_name as created_by_name 
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
    
    // Check access: Staff can only view audits where they are the auditor
    if ($user['role'] === 'Staff' && $audit['auditor_id'] != $user['id']) {
        setFlashMessage('You do not have permission to view this audit.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Define status badge colors
    $status_colors = [
        'Planned' => 'bg-secondary',
        'In Progress' => 'bg-warning',
        'Completed' => 'bg-success'
    ];
    
    // Get checklist statistics
    $stmt = $pdo->prepare("SELECT response, COUNT(*) as count FROM audit_checklist WHERE audit_id = ? GROUP BY response");
    $stmt->execute([$audit_id]);
    $checklist_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_checklist_items = array_sum($checklist_stats);
    $checklist_answered = ($checklist_stats['Yes'] ?? 0) + ($checklist_stats['No'] ?? 0) + ($checklist_stats['N/A'] ?? 0);
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching audit data: ' . $e->getMessage(), 'danger');
    redirect('/modules/audits/list.php');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-eye me-2"></i>Audit Details</h2>
    <div>
        <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Audits
        </a>
        <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
            <?php if ($user['role'] === 'Admin' || $audit['auditor_id'] == $user['id']): ?>
            <a href="<?php echo BASE_URL; ?>/modules/audits/edit.php?id=<?php echo $audit['id']; ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>Edit Audit
            </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Audit Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Audit Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Audit Code:</strong>
                        <div><?php echo htmlspecialchars($audit['audit_code']); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Status:</strong>
                        <div>
                            <span class="badge <?php echo $status_colors[$audit['status']]; ?>">
                                <?php echo htmlspecialchars($audit['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Title:</strong>
                        <div><?php echo htmlspecialchars($audit['title']); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Department:</strong>
                        <div><?php echo htmlspecialchars($audit['department']); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Audit Date:</strong>
                        <div><?php echo formatDate($audit['audit_date']); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Created By:</strong>
                        <div><?php echo htmlspecialchars($audit['created_by_name']); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Assigned Auditor:</strong>
                        <div><?php echo htmlspecialchars($audit['auditor_name'] ?? 'Unassigned'); ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Created At:</strong>
                        <div><?php echo formatDate($audit['created_at'], 'M d, Y H:i'); ?></div>
                    </div>
                </div>
                
                <?php if ($audit['description']): ?>
                <div class="mt-3">
                    <strong>Description:</strong>
                    <div class="mt-1"><?php echo nl2br(htmlspecialchars($audit['description'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Placeholder sections for future phases -->
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="auditTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="checklist-tab" data-bs-toggle="tab" data-bs-target="#checklist" type="button" role="tab">
                            <i class="bi bi-list-check me-2"></i>Checklist
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="findings-tab" data-bs-toggle="tab" data-bs-target="#findings" type="button" role="tab">
                            <i class="bi bi-exclamation-triangle me-2"></i>Findings
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">
                            <i class="bi bi-file-earmark me-2"></i>Documents
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="auditTabsContent">
                    <div class="tab-pane fade show active" id="checklist" role="tabpanel">
                        <?php if ($total_checklist_items == 0): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-list-check fs-1 text-muted"></i>
                            <p class="text-muted mt-3">Checklist not yet started for this audit.</p>
                            <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
                                <?php if ($user['role'] === 'Admin' || $audit['auditor_id'] == $user['id']): ?>
                                <a href="<?php echo BASE_URL; ?>/modules/checklist/fill.php?audit_id=<?php echo $audit['id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i>Start / Fill Checklist
                                </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><strong>Progress:</strong> <?php echo $checklist_answered; ?> of <?php echo $total_checklist_items; ?> answered</span>
                                <div class="btn-group btn-group-sm">
                                    <span class="badge badge-active">Yes: <?php echo $checklist_stats['Yes'] ?? 0; ?></span>
                                    <span class="badge badge-inactive">No: <?php echo $checklist_stats['No'] ?? 0; ?></span>
                                    <span class="badge bg-secondary">N/A: <?php echo $checklist_stats['N/A'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <a href="<?php echo BASE_URL; ?>/modules/checklist/fill.php?audit_id=<?php echo $audit['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil me-2"></i>View / Edit Checklist
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="findings" role="tabpanel">
                        <div class="text-center py-5">
                            <i class="bi bi-exclamation-triangle fs-1 text-muted"></i>
                            <p class="text-muted mt-3">Findings & Issues feature coming in Phase 4</p>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="documents" role="tabpanel">
                        <div class="text-center py-5">
                            <i class="bi bi-file-earmark fs-1 text-muted"></i>
                            <p class="text-muted mt-3">Documents & Evidence feature coming in Phase 5</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
                        <?php if ($user['role'] === 'Admin' || $audit['auditor_id'] == $user['id']): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/audits/edit.php?id=<?php echo $audit['id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Edit Audit
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-list me-2"></i>View All Audits
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Audit Summary -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Audit Summary</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <strong>Code:</strong> <?php echo htmlspecialchars($audit['audit_code']); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Department:</strong> <?php echo htmlspecialchars($audit['department']); ?>
                    </li>
                    <li class="mb-2">
                        <strong>Status:</strong> 
                        <span class="badge <?php echo $status_colors[$audit['status']]; ?>">
                            <?php echo htmlspecialchars($audit['status']); ?>
                        </span>
                    </li>
                    <li class="mb-0">
                        <strong>Auditor:</strong> <?php echo htmlspecialchars($audit['auditor_name'] ?? 'Unassigned'); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
