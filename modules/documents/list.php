<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

$pageTitle = 'Documents';
require_once __DIR__ . '/../../includes/header.php';

$user = getCurrentUser();
$audit_id = intval($_GET['audit_id'] ?? 0);

try {
    // Build base query based on role and audit_id filter
    $where = ['1=1'];
    $params = [];
    
    // Filter by audit_id if provided
    if ($audit_id > 0) {
        $where[] = 'd.audit_id = ?';
        $params[] = $audit_id;
    }
    
    // Role-based access control
    if ($user['role'] === 'Staff') {
        // Staff only sees documents for audits they're relevant to (same pattern as findings)
        $where[] = '(EXISTS (SELECT 1 FROM audits a WHERE a.id = d.audit_id AND a.auditor_id = ?) OR EXISTS (SELECT 1 FROM findings f WHERE f.audit_id = d.audit_id AND f.responsible_user_id = ?))';
        $params[] = $user['id'];
        $params[] = $user['id'];
    } elseif ($user['role'] === 'Auditor') {
        // Auditor sees documents for assigned audits
        $where[] = 'EXISTS (SELECT 1 FROM audits a WHERE a.id = d.audit_id AND a.auditor_id = ?)';
        $params[] = $user['id'];
    }
    // Admin sees all
    
    $where_clause = implode(' AND ', $where);
    
    // Get documents with pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    $stmt = $pdo->prepare("SELECT d.*, a.audit_code, a.title as audit_title, u.full_name as uploaded_by_name 
                          FROM documents d 
                          LEFT JOIN audits a ON d.audit_id = a.id 
                          LEFT JOIN users u ON d.uploaded_by = u.id 
                          WHERE $where_clause 
                          ORDER BY d.upload_date DESC 
                          LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $documents = $stmt->fetchAll();
    
    // Get total count for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents d WHERE $where_clause");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching documents: ' . $e->getMessage(), 'danger');
    $documents = [];
    $total = 0;
    $total_pages = 0;
}

// Helper function to get file icon based on type
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
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-folder me-2"></i>Documents</h2>
    <?php if ($audit_id > 0): ?>
    <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Audit
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($documents)): ?>
        <div class="text-center py-5">
            <i class="bi bi-folder-x fs-1 text-muted"></i>
            <p class="text-muted mt-3">No documents found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Document Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Audit</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                        <td>
                            <i class="bi <?php echo getFileIcon($doc['file_type']); ?> me-1"></i>
                            <?php echo strtoupper(htmlspecialchars($doc['file_type'])); ?>
                        </td>
                        <td><?php echo formatFileSize($doc['file_size']); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $doc['audit_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($doc['audit_code']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($doc['uploaded_by_name']); ?></td>
                        <td><?php echo formatDate($doc['upload_date']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/modules/documents/view.php?id=<?php echo $doc['id']; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
                                    <?php if ($user['role'] === 'Admin' || $doc['uploaded_by'] == $user['id']): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/documents/delete.php?id=<?php echo $doc['id']; ?>" class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $audit_id ? '&audit_id=' . $audit_id : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
