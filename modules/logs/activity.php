<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// Require Admin role
requireRole(['Admin']);

// Process all PHP logic before HTML output
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filter_module = $_GET['module'] ?? '';
$filter_user = intval($_GET['user'] ?? 0);

try {
    // Get all users for filter dropdown
    $stmt = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name");
    $users = $stmt->fetchAll();
    
    // Build WHERE clause for filters
    $where = [];
    $params = [];
    
    if (!empty($filter_module)) {
        $where[] = "al.module = ?";
        $params[] = $filter_module;
    }
    
    if ($filter_user > 0) {
        $where[] = "al.user_id = ?";
        $params[] = $filter_user;
    }
    
    $where_clause = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count for pagination
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_log al" . $where_clause);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    
    // Get activity log with pagination
    $stmt = $pdo->prepare("SELECT al.*, u.full_name as user_name FROM activity_log al LEFT JOIN users u ON al.user_id = u.id" . $where_clause . " ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $activities = $stmt->fetchAll();
    
    // Get unique modules for filter
    $stmt = $pdo->query("SELECT DISTINCT module FROM activity_log ORDER BY module");
    $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching activity log: ' . $e->getMessage(), 'danger');
    $activities = [];
    $total_pages = 1;
    $modules = [];
    $users = [];
}

// Helper function for relative time
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M d, Y H:i', $time);
    }
}

$pageTitle = 'Activity Log';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clock-history me-2"></i>Activity Log</h2>
    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="module" class="form-label">Module</label>
                <select class="form-select" id="module" name="module">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $module): ?>
                    <option value="<?php echo htmlspecialchars($module ?? ''); ?>" <?php echo $filter_module === $module ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($module ?? ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="user" class="form-label">User</label>
                <select class="form-select" id="user" name="user">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>" <?php echo $filter_user == $u['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($u['full_name'] ?? ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter me-2"></i>Filter
                    </button>
                    <a href="<?php echo BASE_URL; ?>/modules/logs/activity.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Activity Log Table -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Activity History</h5>
            <span class="text-muted small">Showing <?php echo count($activities); ?> of <?php echo $total_records; ?> records</span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($activities)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-3">No activity found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td>
                            <div><?php echo timeAgo($activity['created_at']); ?></div>
                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($activity['user_name'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($activity['action'] ?? ''); ?></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($activity['module'] ?? ''); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($activity['description'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&module=<?php echo htmlspecialchars($filter_module ?? ''); ?>&user=<?php echo $filter_user; ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?>
                <li class="page-item active">
                    <span class="page-link"><?php echo $i; ?></span>
                </li>
                <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $i; ?>&module=<?php echo htmlspecialchars($filter_module ?? ''); ?>&user=<?php echo $filter_user; ?>"><?php echo $i; ?></a>
                </li>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
                <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&module=<?php echo htmlspecialchars($filter_module ?? ''); ?>&user=<?php echo $filter_user; ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
