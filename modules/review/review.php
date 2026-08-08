<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

// Require Admin role
requireRole(['Admin']);

$audit_id = intval($_GET['audit_id'] ?? 0);
$error = '';
$warning = '';

// Handle GET validation and redirects FIRST
if ($audit_id <= 0) {
    setFlashMessage('Invalid audit ID.', 'danger');
    redirect('/modules/audits/list.php');
}

$user = getCurrentUser();

try {
    // Get audit data
    $stmt = $pdo->prepare("SELECT a.*, u1.full_name as auditor_name, u2.full_name as created_by_name, u3.full_name as reviewed_by_name 
                          FROM audits a 
                          LEFT JOIN users u1 ON a.auditor_id = u1.id 
                          LEFT JOIN users u2 ON a.created_by = u2.id 
                          LEFT JOIN users u3 ON a.reviewed_by = u3.id 
                          WHERE a.id = ?");
    $stmt->execute([$audit_id]);
    $audit = $stmt->fetch();
    
    if (!$audit) {
        setFlashMessage('Audit not found.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Get checklist items
    $stmt = $pdo->prepare("SELECT * FROM audit_checklist WHERE audit_id = ? ORDER BY id");
    $stmt->execute([$audit_id]);
    $checklist_items = $stmt->fetchAll();
    
    // Get checklist statistics
    $stmt = $pdo->prepare("SELECT response, COUNT(*) as count FROM audit_checklist WHERE audit_id = ? GROUP BY response");
    $stmt->execute([$audit_id]);
    $checklist_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_checklist = count($checklist_items);
    $checklist_yes = $checklist_stats['Yes'] ?? 0;
    $checklist_no = $checklist_stats['No'] ?? 0;
    $checklist_na = $checklist_stats['N/A'] ?? 0;
    $checklist_unanswered = $total_checklist - $checklist_yes - $checklist_no - $checklist_na;
    
    // Get findings
    $stmt = $pdo->prepare("SELECT f.*, u.full_name as responsible_name FROM findings f LEFT JOIN users u ON f.responsible_user_id = u.id WHERE f.audit_id = ? ORDER BY f.created_at");
    $stmt->execute([$audit_id]);
    $findings = $stmt->fetchAll();
    
    // Get findings statistics
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM findings WHERE audit_id = ? GROUP BY status");
    $stmt->execute([$audit_id]);
    $findings_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_findings = count($findings);
    $findings_open = $findings_stats['Open'] ?? 0;
    $findings_resolved = $findings_stats['Resolved'] ?? 0;
    $findings_closed = $findings_stats['Closed'] ?? 0;
    
    // Get documents
    $stmt = $pdo->prepare("SELECT d.*, u.full_name as uploaded_by_name FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id WHERE d.audit_id = ? ORDER BY d.upload_date");
    $stmt->execute([$audit_id]);
    $documents = $stmt->fetchAll();
    
    $total_documents = count($documents);
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching audit data: ' . $e->getMessage(), 'danger');
    redirect('/modules/audits/list.php');
}

// Handle POST logic FIRST, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $auditor_comments = sanitize($_POST['auditor_comments'] ?? '');
    $final_remarks = sanitize($_POST['final_remarks'] ?? '');
    $confirm_complete = isset($_POST['confirm_complete']);
    
    if ($action === 'save_notes') {
        try {
            $stmt = $pdo->prepare("UPDATE audits SET auditor_comments = ?, final_remarks = ? WHERE id = ?");
            $stmt->execute([$auditor_comments ?: null, $final_remarks ?: null, $audit_id]);
            
            // Log review notes save activity
            logActivity($_SESSION['user_id'], 'Reviewed Audit', 'Review', $audit_id, "Added review notes for audit {$audit['audit_code']}");
            
            setFlashMessage('Review notes saved successfully!', 'success');
            redirect('/modules/review/review.php?audit_id=' . $audit_id);
        } catch (PDOException $e) {
            $error = 'Error saving review notes: ' . $e->getMessage();
        }
    } elseif ($action === 'complete_audit') {
        // Check for open findings warning
        if ($findings_open > 0) {
            $warning = "Warning: $findings_open finding(s) are still Open. You can still complete the audit, but consider resolving these findings first.";
        }
        
        if (!$confirm_complete) {
            $error = 'Please confirm you want to complete this audit review.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE audits SET auditor_comments = ?, final_remarks = ?, status = 'Completed', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $stmt->execute([$auditor_comments ?: null, $final_remarks ?: null, $user['id'], $audit_id]);
                
                // Log audit completion activity
                logActivity($_SESSION['user_id'], 'Completed Audit', 'Review', $audit_id, "Completed audit review: {$audit['audit_code']} - {$audit['title']}");
                
                setFlashMessage('Audit completed successfully!', 'success');
                redirect('/modules/audits/view.php?id=' . $audit_id);
            } catch (PDOException $e) {
                $error = 'Error completing audit: ' . $e->getMessage();
            }
        }
    }
}

// Only AFTER all possible redirects, include header and render HTML
$pageTitle = 'Review Audit';
require_once __DIR__ . '/../../includes/header.php';

// Define status badge colors
$status_colors = [
    'Planned' => 'bg-secondary',
    'In Progress' => 'bg-warning',
    'Completed' => 'bg-success'
];

// Define severity colors
$severity_colors = [
    'High' => 'badge-inactive',
    'Medium' => 'bg-warning',
    'Low' => 'badge-active'
];

// Define finding status colors
$finding_status_colors = [
    'Open' => 'badge-inactive',
    'Resolved' => 'badge-active',
    'Closed' => 'bg-secondary'
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-check me-2"></i>Review Audit</h2>
    <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Audit
    </a>
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
                        <strong>Audit Code:</strong> <?php echo htmlspecialchars($audit['audit_code']); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Title:</strong> <?php echo htmlspecialchars($audit['title']); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Department:</strong> <?php echo htmlspecialchars($audit['department']); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Auditor:</strong> <?php echo htmlspecialchars($audit['auditor_name'] ?? 'Unassigned'); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Audit Date:</strong> <?php echo formatDate($audit['audit_date']); ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Status:</strong> 
                        <span class="badge <?php echo $status_colors[$audit['status']]; ?>">
                            <?php echo htmlspecialchars($audit['status']); ?>
                        </span>
                    </div>
                </div>
                <?php if ($audit['description']): ?>
                <hr>
                <div class="mb-0">
                    <strong>Description:</strong>
                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($audit['description'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Checklist Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Checklist Results</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between">
                        <span><strong>Total:</strong> <?php echo $total_checklist; ?> questions</span>
                        <div class="btn-group btn-group-sm">
                            <span class="badge badge-active">Yes: <?php echo $checklist_yes; ?></span>
                            <span class="badge badge-inactive">No: <?php echo $checklist_no; ?></span>
                            <span class="badge bg-secondary">N/A: <?php echo $checklist_na; ?></span>
                            <?php if ($checklist_unanswered > 0): ?>
                            <span class="badge bg-warning">Unanswered: <?php echo $checklist_unanswered; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Response</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($checklist_items as $index => $item): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($item['question_text']); ?></td>
                                <td>
                                    <?php if ($item['response']): ?>
                                        <span class="badge <?php echo $item['response'] === 'Yes' ? 'badge-active' : ($item['response'] === 'No' ? 'badge-inactive' : 'bg-secondary'); ?>">
                                            <?php echo htmlspecialchars($item['response']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['note'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Findings Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Findings Summary</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between">
                        <span><strong>Total:</strong> <?php echo $total_findings; ?> findings</span>
                        <div class="btn-group btn-group-sm">
                            <span class="badge badge-inactive">Open: <?php echo $findings_open; ?></span>
                            <span class="badge badge-active">Resolved: <?php echo $findings_resolved; ?></span>
                            <span class="badge bg-secondary">Closed: <?php echo $findings_closed; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Severity</th>
                                <th>Responsible</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($findings as $f): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($f['finding_title']); ?></td>
                                <td>
                                    <span class="badge <?php echo $severity_colors[$f['severity']]; ?>">
                                        <?php echo htmlspecialchars($f['severity']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($f['responsible_name'] ?? 'Unassigned'); ?></td>
                                <td>
                                    <span class="badge <?php echo $finding_status_colors[$f['status']]; ?>">
                                        <?php echo htmlspecialchars($f['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Documents -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-folder me-2"></i>Uploaded Documents</h5>
            </div>
            <div class="card-body">
                <?php if ($total_documents == 0): ?>
                <p class="text-muted">No documents uploaded.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Type</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                <td><?php echo strtoupper(htmlspecialchars($doc['file_type'])); ?></td>
                                <td><?php echo htmlspecialchars($doc['uploaded_by_name']); ?></td>
                                <td><?php echo formatDate($doc['upload_date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Review Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Review Notes</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($warning): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($warning); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="auditor_comments" class="form-label">Auditor Comments</label>
                        <textarea class="form-control" id="auditor_comments" name="auditor_comments" rows="4"><?php echo htmlspecialchars($audit['auditor_comments'] ?? ''); ?></textarea>
                        <small class="text-muted">General comments about the audit process and observations.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="final_remarks" class="form-label">Final Remarks</label>
                        <textarea class="form-control" id="final_remarks" name="final_remarks" rows="4"><?php echo htmlspecialchars($audit['final_remarks'] ?? ''); ?></textarea>
                        <small class="text-muted">Closing remarks and overall assessment of the audit.</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="save_notes" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Review Notes
                        </button>
                        <button type="submit" name="action" value="complete_audit" class="btn btn-success">
                            <i class="bi bi-check-circle me-2"></i>Complete / Close Audit
                        </button>
                        <a href="<?php echo BASE_URL; ?>/modules/review/report.php?audit_id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary" target="_blank">
                            <i class="bi bi-printer me-2"></i>View Report
                        </a>
                    </div>
                    
                    <div class="mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirm_complete" name="confirm_complete">
                            <label class="form-check-label" for="confirm_complete">
                                I confirm this audit review is complete and ready to be closed
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Review Status</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><strong>Status:</strong> 
                        <span class="badge <?php echo $status_colors[$audit['status']]; ?>">
                            <?php echo htmlspecialchars($audit['status']); ?>
                        </span>
                    </li>
                    <?php if ($audit['reviewed_by']): ?>
                    <li class="mb-2"><strong>Reviewed By:</strong> <?php echo htmlspecialchars($audit['reviewed_by_name']); ?></li>
                    <li class="mb-0"><strong>Reviewed At:</strong> <?php echo formatDate($audit['reviewed_at'], 'M d, Y H:i'); ?></li>
                    <?php else: ?>
                    <li class="mb-0"><strong>Reviewed By:</strong> <span class="text-muted">Not yet reviewed</span></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>/modules/review/report.php?audit_id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-printer me-2"></i>View / Print Report
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Audit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
