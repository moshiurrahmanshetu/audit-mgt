<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

// Process all PHP logic before HTML output
$user = getCurrentUser();
$stats = [];
$recent_audits = [];
$recent_activity = [];

try {
    // Build WHERE clause based on role for statistics
    $where_clause = '';
    $params = [];
    
    if ($user['role'] === 'Admin') {
        // Admin sees all data
        $where_clause = '';
    } elseif ($user['role'] === 'Auditor') {
        // Auditor sees only assigned audits
        $where_clause = ' WHERE auditor_id = ?';
        $params[] = $user['id'];
    } elseif ($user['role'] === 'Staff') {
        // Staff sees only audits where they are responsible for findings
        $where_clause = ' WHERE id IN (SELECT DISTINCT audit_id FROM findings WHERE responsible_user_id = ?)';
        $params[] = $user['id'];
    }
    
    // Get audit statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audits" . $where_clause);
    $stmt->execute($params);
    $stats['total_audits'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audits WHERE status = 'Planned'" . ($where_clause ? ' AND ' . substr($where_clause, 7) : $where_clause));
    $stmt->execute($params);
    $stats['planned_audits'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audits WHERE status = 'In Progress'" . ($where_clause ? ' AND ' . substr($where_clause, 7) : $where_clause));
    $stmt->execute($params);
    $stats['ongoing_audits'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audits WHERE status = 'Completed'" . ($where_clause ? ' AND ' . substr($where_clause, 7) : $where_clause));
    $stmt->execute($params);
    $stats['completed_audits'] = $stmt->fetchColumn();
    
    // Get findings statistics
    $findings_where = '';
    $findings_params = [];
    
    if ($user['role'] === 'Admin') {
        $findings_where = '';
    } elseif ($user['role'] === 'Auditor') {
        $findings_where = ' WHERE f.audit_id IN (SELECT id FROM audits WHERE auditor_id = ?)';
        $findings_params[] = $user['id'];
    } elseif ($user['role'] === 'Staff') {
        $findings_where = ' WHERE f.responsible_user_id = ?';
        $findings_params[] = $user['id'];
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM findings f" . $findings_where . ($findings_where ? ' AND ' : ' WHERE ') . "f.status = 'Open'");
    $stmt->execute($findings_params);
    $stats['open_findings'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM findings f" . $findings_where . ($findings_where ? ' AND ' : ' WHERE ') . "f.status = 'Resolved'");
    $stmt->execute($findings_params);
    $stats['resolved_findings'] = $stmt->fetchColumn();
    
    // Get recent audits (5 most recent)
    $stmt = $pdo->prepare("SELECT id, audit_code, title, status, created_at FROM audits" . $where_clause . " ORDER BY created_at DESC LIMIT 5");
    $stmt->execute($params);
    $recent_audits = $stmt->fetchAll();
    
    // Get recent activity (10 most recent)
    $activity_where = '';
    $activity_params = [];
    
    if ($user['role'] === 'Admin') {
        $activity_where = '';
    } else {
        $activity_where = ' WHERE user_id = ?';
        $activity_params[] = $user['id'];
    }
    
    $stmt = $pdo->prepare("SELECT al.*, u.full_name as user_name FROM activity_log al LEFT JOIN users u ON al.user_id = u.id" . $activity_where . " ORDER BY al.created_at DESC LIMIT 10");
    $stmt->execute($activity_params);
    $recent_activity = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching dashboard data: ' . $e->getMessage(), 'danger');
    $stats = [
        'total_audits' => 0,
        'planned_audits' => 0,
        'ongoing_audits' => 0,
        'completed_audits' => 0,
        'open_findings' => 0,
        'resolved_findings' => 0
    ];
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
        return date('M d, Y', $time);
    }
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="card-subtitle mb-1 text-muted">Total Audits</h6>
                        <h3 class="card-title mb-0"><?php echo $stats['total_audits']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-secondary">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="card-subtitle mb-1 text-muted">Planned</h6>
                        <h3 class="card-title mb-0"><?php echo $stats['planned_audits']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="card-subtitle mb-1 text-muted">In Progress</h6>
                        <h3 class="card-title mb-0"><?php echo $stats['ongoing_audits']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="card-subtitle mb-1 text-muted">Completed</h6>
                        <h3 class="card-title mb-0"><?php echo $stats['completed_audits']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="card-subtitle mb-1 text-muted">Open Findings</h6>
                        <h3 class="card-title mb-0"><?php echo $stats['open_findings']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info">
                        <i class="bi bi-check2-all"></i>
                    </div>
                    <div class="ms-3">
                        <h6 class="card-subtitle mb-1 text-muted">Resolved Findings</h6>
                        <h3 class="card-title mb-0"><?php echo $stats['resolved_findings']; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Recent Audits -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Audits</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_audits)): ?>
                <p class="text-muted">No audits found.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Audit Code</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_audits as $audit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($audit['code'] ?? $audit['audit_code'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($audit['title'] ?? ''); ?></td>
                                <td>
                                    <span class="badge <?php echo $audit['status'] === 'Completed' ? 'bg-success' : ($audit['Status'] === 'In Progress' ? 'bg-warning' : 'bg-secondary'); ?>">
                                        <?php echo htmlspecialchars($audit['status'] ?? ''); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($audit['created_at']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/modules/audits/view.php?id=<?php echo $audit['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Recent Activity -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activity)): ?>
                <p class="text-muted">No recent activity.</p>
                <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-start">
                            <div class="activity-icon bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($activity['user_name'] ?? 'Unknown'); ?></strong>
                                    <span class="text-muted small"><?php echo timeAgo($activity['created_at']); ?></span>
                                </p>
                                <p class="mb-1 small">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($activity['module'] ?? ''); ?></span>
                                    <?php echo htmlspecialchars($activity['action'] ?? ''); ?>
                                </p>
                                <?php if ($activity['description']): ?>
                                <p class="mb-0 small text-muted"><?php echo htmlspecialchars($activity['description'] ?? ''); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (hasAnyRole(['Admin', 'Auditor'])): ?>
                    <a href="<?php echo BASE_URL; ?>/modules/audits/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create Audit
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/modules/audits/list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-clipboard-check me-2"></i>View All Audits
                    </a>
                    <a href="<?php echo BASE_URL; ?>/modules/findings/list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-exclamation-triangle me-2"></i>View Findings
                    </a>
                    <?php if (hasRole('Admin')): ?>
                    <a href="<?php echo BASE_URL; ?>/modules/logs/activity.php" class="btn btn-outline-secondary">
                        <i class="bi bi-clock-history me-2"></i>Activity Log
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
