<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Checklist Templates';
require_once __DIR__ . '/../../includes/header.php';

// TODO: log activity in Phase 7

// Require Admin role
requireRole(['Admin']);

$error = '';
$success = '';

try {
    // Get all checklist templates
    $stmt = $pdo->query("SELECT * FROM checklist_templates ORDER BY id");
    $templates = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('Error fetching checklist templates: ' . $e->getMessage(), 'danger');
    $templates = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $question_text = sanitize($_POST['question_text'] ?? '');
        
        if (empty($question_text)) {
            $error = 'Question text is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO checklist_templates (question_text, is_active) VALUES (?, 1)");
                $stmt->execute([$question_text]);
                $new_template_id = $pdo->lastInsertId();
                
                // Log checklist template creation activity
                logActivity($_SESSION['user_id'], 'Created Checklist Template', 'Checklist', $new_template_id, "Created checklist template: {$question_text}");
                
                setFlashMessage('Checklist question added successfully!', 'success');
                redirect('/modules/checklist/manage.php');
            } catch (PDOException $e) {
                $error = 'Error adding question: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $template_id = intval($_POST['template_id'] ?? 0);
        $question_text = sanitize($_POST['question_text'] ?? '');
        
        if ($template_id <= 0 || empty($question_text)) {
            $error = 'Invalid input.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE checklist_templates SET question_text = ? WHERE id = ?");
                $stmt->execute([$question_text, $template_id]);
                
                // Log checklist template update activity
                logActivity($_SESSION['user_id'], 'Updated Checklist Template', 'Checklist', $template_id, "Updated checklist template: {$question_text}");
                
                setFlashMessage('Checklist question updated successfully!', 'success');
                redirect('/modules/checklist/manage.php');
            } catch (PDOException $e) {
                $error = 'Error updating question: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'toggle') {
        $template_id = intval($_POST['template_id'] ?? 0);
        
        if ($template_id <= 0) {
            $error = 'Invalid template ID.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE checklist_templates SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$template_id]);
                setFlashMessage('Checklist question status updated!', 'success');
                redirect('/modules/checklist/manage.php');
            } catch (PDOException $e) {
                $error = 'Error updating status: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-list-check me-2"></i>Checklist Templates</h2>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Question</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="2" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Add Question
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Questions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($templates)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-list-x fs-1 text-muted"></i>
                    <p class="text-muted mt-3">No checklist questions found.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($template['question_text']); ?></td>
                                <td>
                                    <?php if ($template['is_active']): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDate($template['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-action" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $template['id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                            <button type="submit" class="btn btn-outline-secondary btn-action" title="<?php echo $template['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="bi bi-<?php echo $template['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $template['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Question</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="edit_question_text<?php echo $template['id']; ?>" class="form-label">Question Text</label>
                                                    <textarea class="form-control" id="edit_question_text<?php echo $template['id']; ?>" name="question_text" rows="3" required><?php echo htmlspecialchars($template['question_text']); ?></textarea>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-check-lg me-2"></i>Update
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Template Info</h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Total Questions:</strong> <?php echo count($templates); ?></p>
                <p class="mb-2"><strong>Active:</strong> <?php echo count(array_filter($templates, fn($t) => $t['is_active'])); ?></p>
                <p class="mb-0"><strong>Inactive:</strong> <?php echo count(array_filter($templates, fn($t) => !$t['is_active'])); ?></p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-clipboard-check me-2"></i>Manage Audits
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
