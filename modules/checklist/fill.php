<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Fill Audit Checklist';
require_once __DIR__ . '/../../includes/header.php';

// TODO: log activity in Phase 7

$audit_id = intval($_GET['audit_id'] ?? 0);
$error = '';
$success = '';

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
    
    // Check access: Admin can access any, Auditor only assigned audits, Staff only assigned audits (view-only)
    if ($user['role'] === 'Auditor' && $audit['auditor_id'] != $user['id']) {
        setFlashMessage('You can only fill checklists for audits assigned to you.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    if ($user['role'] === 'Staff' && $audit['auditor_id'] != $user['id']) {
        setFlashMessage('You can only view checklists for audits assigned to you.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Auto-populate checklist if no rows exist for this audit
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_checklist WHERE audit_id = ?");
    $stmt->execute([$audit_id]);
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Copy all active template questions
        $stmt = $pdo->query("SELECT question_text FROM checklist_templates WHERE is_active = 1 ORDER BY id");
        $templates = $stmt->fetchAll();
        
        foreach ($templates as $template) {
            $stmt = $pdo->prepare("INSERT INTO audit_checklist (audit_id, question_text) VALUES (?, ?)");
            $stmt->execute([$audit_id, $template['question_text']]);
        }
    }
    
    // Get checklist items for this audit
    $stmt = $pdo->prepare("SELECT * FROM audit_checklist WHERE audit_id = ? ORDER BY id");
    $stmt->execute([$audit_id]);
    $checklist_items = $stmt->fetchAll();
    
    // Calculate summary statistics
    $stmt = $pdo->prepare("SELECT response, COUNT(*) as count FROM audit_checklist WHERE audit_id = ? GROUP BY response");
    $stmt->execute([$audit_id]);
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_answered = ($stats['Yes'] ?? 0) + ($stats['No'] ?? 0) + ($stats['N/A'] ?? 0);
    $total_questions = count($checklist_items);
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching checklist data: ' . $e->getMessage(), 'danger');
    redirect('/modules/audits/list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasAnyRole(['Admin', 'Auditor'])) {
    $responses = $_POST['response'] ?? [];
    $notes = $_POST['note'] ?? [];
    
    try {
        foreach ($checklist_items as $item) {
            $response = $responses[$item['id']] ?? null;
            $note = $notes[$item['id']] ?? null;
            
            $stmt = $pdo->prepare("UPDATE audit_checklist SET response = ?, note = ? WHERE id = ?");
            $stmt->execute([$response ?: null, $note ?: null, $item['id']]);
        }
        
        setFlashMessage('Checklist saved successfully!', 'success');
        redirect('/modules/checklist/fill.php?audit_id=' . $audit_id);
    } catch (PDOException $e) {
        $error = 'Error saving checklist: ' . $e->getMessage();
    }
}

// Determine if user can edit
$can_edit = hasAnyRole(['Admin', 'Auditor']);
if ($user['role'] === 'Auditor' && $audit['auditor_id'] != $user['id']) {
    $can_edit = false;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-list-check me-2"></i>Audit Checklist</h2>
    <div>
        <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Audit
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i><?php echo htmlspecialchars($audit['title']); ?></h5>
                <small class="text-muted">Audit Code: <?php echo htmlspecialchars($audit['audit_code']); ?></small>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><strong>Progress:</strong> <?php echo $total_answered; ?> of <?php echo $total_questions; ?> answered</span>
                        <div class="btn-group btn-group-sm">
                            <span class="badge badge-active">Yes: <?php echo $stats['Yes'] ?? 0; ?></span>
                            <span class="badge badge-inactive">No: <?php echo $stats['No'] ?? 0; ?></span>
                            <span class="badge bg-secondary">N/A: <?php echo $stats['N/A'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <?php foreach ($checklist_items as $index => $item): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="mb-0"><?php echo ($index + 1) . '. ' . htmlspecialchars($item['question_text']); ?></h6>
                                <?php if ($item['response']): ?>
                                <span class="badge <?php echo $item['response'] === 'Yes' ? 'badge-active' : ($item['response'] === 'No' ? 'badge-inactive' : 'bg-secondary'); ?>">
                                    <?php echo htmlspecialchars($item['response']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Response:</label>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="response[<?php echo $item['id']; ?>]" id="yes_<?php echo $item['id']; ?>" value="Yes" <?php echo $item['response'] === 'Yes' ? 'checked' : ''; ?> <?php echo $can_edit ? '' : 'disabled'; ?>>
                                    <label class="btn btn-outline-success" for="yes_<?php echo $item['id']; ?>">Yes</label>
                                    
                                    <input type="radio" class="btn-check" name="response[<?php echo $item['id']; ?>]" id="no_<?php echo $item['id']; ?>" value="No" <?php echo $item['response'] === 'No' ? 'checked' : ''; ?> <?php echo $can_edit ? '' : 'disabled'; ?>>
                                    <label class="btn btn-outline-danger" for="no_<?php echo $item['id']; ?>">No</label>
                                    
                                    <input type="radio" class="btn-check" name="response[<?php echo $item['id']; ?>]" id="na_<?php echo $item['id']; ?>" value="N/A" <?php echo $item['response'] === 'N/A' ? 'checked' : ''; ?> <?php echo $can_edit ? '' : 'disabled'; ?>>
                                    <label class="btn btn-outline-secondary" for="na_<?php echo $item['id']; ?>">N/A</label>
                                </div>
                            </div>
                            
                            <div class="mb-0">
                                <label for="note_<?php echo $item['id']; ?>" class="form-label">Note (optional):</label>
                                <textarea class="form-control" id="note_<?php echo $item['id']; ?>" name="note[<?php echo $item['id']; ?>]" rows="2" <?php echo $can_edit ? '' : 'readonly'; ?>><?php echo htmlspecialchars($item['note'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($can_edit): ?>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Checklist
                        </button>
                        <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Checklist Summary</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Progress</span>
                        <span><?php echo $total_questions > 0 ? round(($total_answered / $total_questions) * 100) : 0; ?>%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar" style="width: <?php echo $total_questions > 0 ? round(($total_answered / $total_questions) * 100) : 0; ?>%"></div>
                    </div>
                </div>
                
                <ul class="list-unstyled mb-0">
                    <li class="mb-2 d-flex justify-content-between">
                        <span>Total Questions:</span>
                        <strong><?php echo $total_questions; ?></strong>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-success">Yes:</span>
                        <strong><?php echo $stats['Yes'] ?? 0; ?></strong>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-danger">No:</span>
                        <strong><?php echo $stats['No'] ?? 0; ?></strong>
                    </li>
                    <li class="mb-0 d-flex justify-content-between">
                        <span class="text-muted">N/A:</span>
                        <strong><?php echo $stats['N/A'] ?? 0; ?></strong>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Audit Info</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><strong>Code:</strong> <?php echo htmlspecialchars($audit['audit_code']); ?></li>
                    <li class="mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($audit['department']); ?></li>
                    <li class="mb-2"><strong>Auditor:</strong> <?php echo htmlspecialchars($audit['auditor_name'] ?? 'Unassigned'); ?></li>
                    <li class="mb-2"><strong>Date:</strong> <?php echo formatDate($audit['audit_date']); ?></li>
                    <li class="mb-0"><strong>Status:</strong> 
                        <span class="badge <?php echo $audit['status'] === 'Completed' ? 'badge-active' : ($audit['status'] === 'In Progress' ? 'bg-warning' : 'bg-secondary'); ?>">
                            <?php echo htmlspecialchars($audit['status']); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
