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
    
    // Check access: Admin can upload to any audit, Auditor only to assigned audits
    if ($user['role'] === 'Auditor' && $audit['auditor_id'] != $user['id']) {
        setFlashMessage('You can only upload documents for audits assigned to you.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching audit data: ' . $e->getMessage(), 'danger');
    redirect('/modules/audits/list.php');
}

// Allowed file types and MIME types
$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'xls', 'docx', 'doc'];
$allowed_mime_types = [
    'application/pdf',
    'image/jpeg',
    'image/jpg',
    'image/png',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/msword'
];
$max_file_size = 10 * 1024 * 1024; // 10MB

// Handle POST logic FIRST, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_name = sanitize($_POST['document_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $file = $_FILES['file'] ?? null;
    
    // Validation
    if (empty($document_name)) {
        $error = 'Document name is required.';
    } elseif (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please select a file to upload.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error: ' . $file['error'];
    } elseif ($file['size'] > $max_file_size) {
        $error = 'File size exceeds maximum limit of 10MB.';
    } else {
        // Check file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            $error = 'Invalid file type. Allowed: PDF, JPG, PNG, XLSX, DOCX.';
        } else {
            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_mime_types)) {
                $error = 'Invalid file type detected. Allowed: PDF, JPG, PNG, XLSX, DOCX.';
            } else {
                try {
                    // Generate unique filename
                    $unique_name = uniqid('doc_', true) . '_' . time() . '.' . $file_extension;
                    
                    // Ensure upload directory exists
                    if (!is_dir(DOCUMENTS_PATH)) {
                        mkdir(DOCUMENTS_PATH, 0755, true);
                    }
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], DOCUMENTS_PATH . '/' . $unique_name)) {
                        // Insert into database
                        $stmt = $pdo->prepare("INSERT INTO documents (audit_id, document_name, file_name, file_type, file_size, description, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $audit_id,
                            $document_name,
                            $unique_name,
                            $file_extension,
                            $file['size'],
                            $description ?: null,
                            $user['id']
                        ]);
                        $new_document_id = $pdo->lastInsertId();
                        
                        // Log document upload activity
                        logActivity($user['id'], 'Uploaded Document', 'Document', $new_document_id, "Uploaded document: {$document_name}");
                        
                        setFlashMessage('Document uploaded successfully!', 'success');
                        redirect('/modules/audits/view.php?id=' . $audit_id);
                    } else {
                        $error = 'Failed to move uploaded file.';
                    }
                } catch (PDOException $e) {
                    $error = 'Error saving document: ' . $e->getMessage();
                }
            }
        }
    }
}

// Only AFTER all possible redirects, include header and render HTML
$pageTitle = 'Upload Document';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cloud-upload me-2"></i>Upload Document</h2>
    <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Audit
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Audit:</strong> <?php echo htmlspecialchars($audit['audit_code'] ?? '' . ' - ' . $audit['title'] ?? ''); ?>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="document_name" class="form-label">Document Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="document_name" name="document_name" value="<?php echo htmlspecialchars($_POST['document_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="file" name="file" required>
                        <small class="text-muted">Allowed: PDF, JPG, PNG, XLSX, DOCX — Max 10MB</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Security Note:</strong> File uploads are validated for type and size. Only allowed file types will be accepted.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i>Upload Document
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
