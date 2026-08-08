<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$pageTitle = 'Audits';
require_once __DIR__ . '/../../includes/header.php';

// TODO: log activity in Phase 7

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$department_filter = $_GET['department'] ?? '';
$page = intval($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Build WHERE clause for filtering
    $where = [];
    $params = [];
    
    // Role-based visibility
    $user = getCurrentUser();
    if ($user['role'] === 'Staff') {
        // Staff only sees audits where they are the auditor
        $where[] = "a.auditor_id = ?";
        $params[] = $user['id'];
    }
    
    if (!empty($status_filter)) {
        $where[] = "a.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($department_filter)) {
        $where[] = "a.department = ?";
        $params[] = $department_filter;
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) FROM audits a " . $where_clause;
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_audits = $stmt->fetchColumn();
    $total_pages = ceil($total_audits / $per_page);
    
    // Get audits with pagination
    $sql = "SELECT a.*, 
            u1.full_name as auditor_name,
            u2.full_name as created_by_name
            FROM audits a
            LEFT JOIN users u1 ON a.auditor_id = u1.id
            LEFT JOIN users u2 ON a.created_by = u2.id
            $where_clause
            ORDER BY a.created_at DESC
            LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $audits = $stmt->fetchAll();
    
    // Get unique departments for filter
    $stmt = $pdo->query("SELECT DISTINCT department FROM audits ORDER BY department");
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching audits: ' . $e->getMessage(), 'danger');
    $audits = [];
    $departments = [];
    $total_audits = 0;
    $total_pages = 1;
}

// Define status badge colors
$status_colors = [
    'Planned' => 'bg-secondary',
    'In Progress' => 'bg-warning',
    'Completed' => 'bg-success'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clipboard-check me-2"></i>Audit Management</h2>
    <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
    <a href="<?php echo BASE_URL; ?>/modules/audits/create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Create Audit
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Planned" <?php echo $status_filter === 'Planned' ? 'selected' : ''; ?>>Planned</option>
                    <option value="In Progress" <?php echo $status_filter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="department" class="form-label">Department</label>
                <select class="form-select" id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-funnel me-2"></i>Filter
                </button>
                <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($audits)): ?>
        <div class="text-center py-5">
            <i class="bi bi-clipboard-x fs-1 text-muted"></i>
            <p class="text-muted mt-3">No audits found.</p>
            <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
            <a href="<?php echo BASE_URL; ?>/modules/audits/create.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Create Audit
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Audit Code</th>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Auditor</th>
                        <th>Audit Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audits as $audit): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($audit['audit_code'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($audit['title'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($audit['department'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($audit['auditor_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo formatDate($audit['audit_date']); ?></td>
                        <td>
                            <span class="badge <?php echo $status_colors[$audit['status']]; ?>">
                                <?php echo htmlspecialchars($audit['status'] ?? ''); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit['id']; ?>" class="btn btn-outline-primary btn-action" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
                                    <?php if ($user['role'] === 'Admin' || $audit['auditor_id'] == $user['id']): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/audits/edit.php?id=<?php echo $audit['id']; ?>" class="btn btn-outline-secondary btn-action" title="Edit">
                                        <i class="bi bi-pencil"></i>
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
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo htmlspecialchars($status_filter); ?>&department=<?php echo htmlspecialchars($department_filter); ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($status_filter); ?>&department=<?php echo htmlspecialchars($department_filter); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo htmlspecialchars($status_filter); ?>&department=<?php echo htmlspecialchars($department_filter); ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
