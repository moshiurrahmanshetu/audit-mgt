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
    $stmt = $pdo->prepare("SELECT d.*, a.audit_code, a.title as audit_title, u.full_name as uploaded_by_name 
                          FROM documents d 
                          LEFT JOIN audits a ON d.audit_id = a.id 
                          LEFT JOIN users u ON d.uploaded_by = u.id 
                          WHERE d.id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        setFlashMessage('Document not found.', 'danger');
        redirect('/modules/documents/list.php');
    }
    
    // Check access: Staff only if they're relevant to the audit
    if ($user['role'] === 'Staff') {
        $stmt = $pdo->prepare("SELECT auditor_id FROM audits WHERE id = ?");
        $stmt->execute([$document['audit_id']]);
        $audit = $stmt->fetch();
        
        // Staff can view if audit is assigned to them or they're responsible for findings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM findings WHERE audit_id = ? AND responsible_user_id = ?");
        $stmt->execute([$document['audit_id'], $user['id']]);
        $has_findings = $stmt->fetchColumn();
        
        if ($audit['auditor_id'] != $user['id'] && !$has_findings) {
            setFlashMessage('You do not have permission to view this document.', 'danger');
            redirect('/modules/documents/list.php');
        }
    }
    
    // Auditor access: only if audit is assigned to them
    if ($user['role'] === 'Auditor') {
        $stmt = $pdo->prepare("SELECT auditor_id FROM audits WHERE id = ?");
        $stmt->execute([$document['audit_id']]);
        $audit = $stmt->fetch();
        
        if ($audit['auditor_id'] != $user['id']) {
            setFlashMessage('You do not have permission to view this document.', 'danger');
            redirect('/modules/documents/list.php');
        }
    }
    
    // Check if file exists
    $file_path = DOCUMENTS_PATH . '/' . $document['file_name'];
    if (!file_exists($file_path)) {
        setFlashMessage('File not found on server.', 'danger');
        redirect('/modules/documents/list.php');
    }
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching document data: ' . $e->getMessage(), 'danger');
    redirect('/modules/documents/list.php');
}

// Handle download/view
if (isset($_GET['download']) || isset($_GET['view'])) {
    $file_type = strtolower($document['file_type']);
    $is_image = in_array($file_type, ['jpg', 'jpeg', 'png']);
    
    // For images, if view mode is requested, show inline
    if ($is_image && isset($_GET['view'])) {
        $mime_type = mime_content_type($file_path);
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: public');
        readfile($file_path);
        exit;
    }
    
    // For all other cases (PDF, Office files, or download mode for images), force download
    $mime_type = mime_content_type($file_path);
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $document['document_name'] . '.' . $document['file_type'] . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: public');
    readfile($file_path);
    exit;
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Helper function to get file icon
function getFileIcon($file_type) {
    $file_type = strtolower($file_type);
    switch ($file_type) {
        case 'pdf':
            return 'bi-file-earmark-pdf';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return 'bi-file-earmark-image';
        case 'xlsx':
        case 'xls':
            return 'bi-file-earmark-excel';
        case 'docx':
        case 'doc':
            return 'bi-file-earmark-word';
        default:
            return 'bi-file-earmark';
    }
}

// Only AFTER all possible redirects, include header and render HTML
$pageTitle = 'View Document';
require_once __DIR__ . '/../../includes/header.php';

$file_type = strtolower($document['file_type']);
$is_image = in_array($file_type, ['jpg', 'jpeg', 'png']);
$is_pdf = $file_type === 'pdf';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-eye me-2"></i>View Document</h2>
    <a href="<?php echo BASE_URL; ?>/modules/documents/list.php<?php echo $document['audit_id'] ? '?audit_id=' . $document['audit_id'] : ''; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Documents
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?php echo htmlspecialchars($document['document_name']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Audit:</strong>
                        <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $document['audit_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($document['audit_code'] . ' - ' . $document['audit_title']); ?>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <strong>Type:</strong>
                        <i class="bi <?php echo getFileIcon($document['file_type']); ?> me-1"></i>
                        <?php echo strtoupper(htmlspecialchars($document['file_type'])); ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Size:</strong> <?php echo formatFileSize($document['file_size']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Uploaded By:</strong> <?php echo htmlspecialchars($document['uploaded_by_name']); ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Upload Date:</strong> <?php echo formatDate($document['upload_date']); ?>
                    </div>
                    <div class="col-md-6">
                        <strong>File Name:</strong> <?php echo htmlspecialchars($document['file_name']); ?>
                    </div>
                </div>
                
                <?php if ($document['description']): ?>
                <hr>
                <div class="mb-3">
                    <strong>Description:</strong>
                    <div class="mt-2"><?php echo nl2br(htmlspecialchars($document['description'])); ?></div>
                </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="d-flex gap-2">
                    <a href="<?php echo BASE_URL; ?>/modules/documents/view.php?id=<?php echo $document['id']; ?>&download=1" class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Download
                    </a>
                    <?php if ($is_image): ?>
                    <a href="<?php echo BASE_URL; ?>/modules/documents/view.php?id=<?php echo $document['id']; ?>&view=1" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-2"></i>View Inline
                    </a>
                    <?php endif; ?>
                    <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
                        <?php if ($user['role'] === 'Admin' || $document['uploaded_by'] == $user['id']): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/documents/delete.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-2"></i>Delete
                        </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($is_image): ?>
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Preview</h6>
            </div>
            <div class="card-body text-center">
                <img src="<?php echo BASE_URL; ?>/modules/documents/view.php?id=<?php echo $document['id']; ?>&view=1" alt="Document Preview" class="img-fluid" style="max-height: 500px;">
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Document Details</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><strong>Uploaded:</strong> <?php echo formatDate($document['upload_date'], 'M d, Y H:i'); ?></li>
                    <li class="mb-2"><strong>Size:</strong> <?php echo formatFileSize($document['file_size']); ?></li>
                    <li class="mb-2"><strong>Type:</strong> <?php echo strtoupper(htmlspecialchars($document['file_type'])); ?></li>
                    <li class="mb-0"><strong>ID:</strong> #<?php echo $document['id']; ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
