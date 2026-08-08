<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

$pageTitle = 'Findings';
require_once __DIR__ . '/../../includes/header.php';

$user = getCurrentUser();
$audit_id = intval($_GET['audit_id'] ?? 0);
$status_filter = $_GET['status'] ?? '';
$severity_filter = $_GET['severity'] ?? '';

try {
    // Build base query based on role and filters
    $where = ['1=1'];
    $params = [];
    
    // Filter by audit_id if provided
    if ($audit_id > 0) {
        $where[] = 'f.audit_id = ?';
        $params[] = $audit_id;
    }
    
    // Filter by status
    if (!empty($status_filter) && in_array($status_filter, ['Open', 'Resolved', 'Closed'])) {
        $where[] = 'f.status = ?';
        $params[] = $status_filter;
    }
    
    // Filter by severity
    if (!empty($severity_filter) && in_array($severity_filter, ['High', 'Medium', 'Low'])) {
        $where[] = 'f.severity = ?';
        $params[] = $severity_filter;
    }
    
    // Role-based access control
    if ($user['role'] === 'Staff') {
        // Staff only sees findings where they are responsible
        $where[] = '(f.responsible_user_id = ? OR f.responsible_user_id IS NULL)';
        $params[] = $user['id'];
    } elseif ($user['role'] === 'Auditor') {
        // Auditor sees findings for assigned audits OR where they are responsible
        $where[] = '(EXISTS (SELECT 1 FROM audits a WHERE a.id = f.audit_id AND a.auditor_id = ?) OR f.responsible_user_id = ?)';
        $params[] = $user['id'];
        $params[] = $user['id'];
    }
    // Admin sees all
    
    $where_clause = implode(' AND ', $where);
    
    // Get findings with pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    $stmt = $pdo->prepare("SELECT f.*, a.audit_code, a.title as audit_title, u1.full_name as responsible_name, u2.full_name as created_by_name 
                          FROM findings f 
                          LEFT JOIN audits a ON f.audit_id = a.id 
                          LEFT JOIN users u1 ON f.responsible_user_id = u1.id 
                          LEFT JOIN users u2 ON f.created_by = u2.id 
                          WHERE $where_clause 
                          ORDER BY f.created_at DESC 
                          LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $findings = $stmt->fetchAll();
    
    // Get total count for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM findings f WHERE $where_clause");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching findings: ' . $e->getMessage(), 'danger');
    $findings = [];
    $total = 0;
    $total_pages = 0;
}

// Define severity colors
$severity_colors = [
    'High' => 'badge-inactive',
    'Medium' => 'bg-warning',
    'Low' => 'badge-active'
];

// Define status colors
$status_colors = [
    'Open' => 'badge-inactive',
    'Resolved' => 'badge-active',
    'Closed' => 'bg-secondary'
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-exclamation-triangle me-2"></i>Findings</h2>
    <?php if ($audit_id > 0): ?>
    <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit_id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Audit
    </a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <?php if ($audit_id > 0): ?>
            <input type="hidden" name="audit_id" value="<?php echo $audit_id; ?>">
            <?php endif; ?>
            
            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Open" <?php echo $status_filter === 'Open' ? 'selected' : ''; ?>>Open</option>
                    <option value="Resolved" <?php echo $status_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="Closed" <?php echo $status_filter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="severity" class="form-label">Filter by Severity</label>
                <select class="form-select" id="severity" name="severity">
                    <option value="">All Severities</option>
                    <option value="High" <?php echo $severity_filter === 'High' ? 'selected' : ''; ?>>High</option>
                    <option value="Medium" <?php echo $severity_filter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="Low" <?php echo $severity_filter === 'Low' ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>
            
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-filter me-2"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($findings)): ?>
        <div class="text-center py-5">
            <i class="bi bi-check-circle fs-1 text-muted"></i>
            <p class="text-muted mt-3">No findings found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Finding Title</th>
                        <th>Audit</th>
                        <th>Severity</th>
                        <th>Responsible</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($findings as $finding): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($finding['finding_title']); ?></td>
                        <td>
                            <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $finding['audit_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($finding['audit_code']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge <?php echo $severity_colors[$finding['severity']]; ?>">
                                <?php echo htmlspecialchars($finding['severity']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($finding['responsible_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo $finding['due_date'] ? formatDate($finding['due_date']) : '-'; ?></td>
                        <td>
                            <span class="badge <?php echo $status_colors[$finding['status']]; ?>">
                                <?php echo htmlspecialchars($finding['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/modules/findings/view.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
                                    <?php if ($user['role'] === 'Admin' || $finding['created_by'] == $user['id']): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/findings/edit.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($finding['status'] === 'Open' && ($user['role'] === 'Admin' || $finding['responsible_user_id'] == $user['id'])): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/findings/resolve.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-success">
                                        <i class="bi bi-check"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($finding['status'] === 'Resolved' && ($user['role'] === 'Admin' || $finding['created_by'] == $user['id'])): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/findings/close.php?id=<?php echo $finding['id']; ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
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
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $audit_id ? '&audit_id=' . $audit_id : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $severity_filter ? '&severity=' . $severity_filter : ''; ?>">
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
