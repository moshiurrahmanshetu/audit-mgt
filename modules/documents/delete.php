<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

$document_id = intval($_GET['id'] ?? 0);

// Handle GET validation and redirects FIRST
if ($document_id <= 0) {
    setFlashMessage('Invalid document ID.', 'danger');
    redirect('/modules/documents/list.php');
}

$user = getCurrentUser();

try {
    // Get document data
    $stmt = $pdo->prepare("SELECT d.*, a.audit_id FROM documents d LEFT JOIN audits a ON d.audit_id = a.id WHERE d.id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        setFlashMessage('Document not found.', 'danger');
        redirect('/modules/documents/list.php');
    }
    
    // Check access: Admin can delete any, Auditor can only delete documents they uploaded
    if ($user['role'] === 'Auditor' && $document['uploaded_by'] != $user['id']) {
        setFlashMessage('You can only delete documents you uploaded.', 'danger');
        redirect('/modules/documents/view.php?id=' . $document_id);
    }
    
    // Handle POST for confirmation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $confirm = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';
        
        if ($confirm) {
            // Delete physical file
            $file_path = DOCUMENTS_PATH . '/' . $document['file_name'];
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    setFlashMessage('Warning: Database record deleted but file could not be removed from server.', 'warning');
                }
            } else {
                // File doesn't exist, but we'll still remove the DB record
                setFlashMessage('File not found on server, but database record removed.', 'warning');
            }
            
            // Delete database record
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);
            
            // Log document deletion activity
            logActivity($_SESSION['user_id'], 'Deleted Document', 'Document', $document_id, "Deleted document: {$document['document_name']}");
            
            if (!isset($_SESSION['flash_message'])) {
                setFlashMessage('Document deleted successfully!', 'success');
            }
            
            redirect('/modules/audits/view.php?id=' . $document['audit_id']);
        } else {
            setFlashMessage('Document deletion cancelled.', 'info');
            redirect('/modules/documents/view.php?id=' . $document_id);
        }
    }
    
} catch (PDOException $e) {
    setFlashMessage('Error deleting document: ' . $e->getMessage(), 'danger');
    redirect('/modules/documents/view.php?id=' . $document_id);
}

// Only AFTER all possible redirects, include header and render HTML
$pageTitle = 'Delete Document';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-trash me-2"></i>Delete Document</h2>
    <a href="<?php echo BASE_URL; ?>/modules/documents/view.php?id=<?php echo $document_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Document
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Are you sure you want to delete this document?</strong>
                </div>
                
                <div class="mb-4">
                    <p><strong>Document:</strong> <?php echo htmlspecialchars($document['document_name']); ?></p>
                    <p><strong>File:</strong> <?php echo htmlspecialchars($document['file_name']); ?></p>
                    <p><strong>Type:</strong> <?php echo strtoupper(htmlspecialchars($document['file_type'])); ?></p>
                    <p><strong>Size:</strong> <?php echo number_format($document['file_size'] / 1024, 2); ?> KB</p>
                </div>
                
                <form method="POST" action="">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        This action will permanently delete the document file from the server and remove the database record. This action cannot be undone.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="confirm" value="yes" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Yes, Delete Document
                        </button>
                        <a href="<?php echo BASE_URL; ?>/modules/documents/view.php?id=<?php echo $document_id; ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
