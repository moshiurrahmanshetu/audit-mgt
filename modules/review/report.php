<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/db.php';

// TODO: log activity in Phase 7

$audit_id = intval($_GET['audit_id'] ?? 0);

// Handle GET validation and redirects FIRST
if ($audit_id <= 0) {
    setFlashMessage('Invalid audit ID.', 'danger');
    redirect('/modules/audits/list.php');
}

$user = getCurrentUser();

try {
    // Get audit data
    $stmt = $pdo->prepare("SELECT a.*, u1.full_name as auditor_name, u2.full_name as created_by_name, u3.full_name as reviewed_by_name 
                          FROM audits a 
                          LEFT JOIN users u1 ON a.auditor_id = u1.id 
                          LEFT JOIN users u2 ON a.created_by = u2.id 
                          LEFT JOIN users u3 ON a.reviewed_by = u3.id 
                          WHERE a.id = ?");
    $stmt->execute([$audit_id]);
    $audit = $stmt->fetch();
    
    if (!$audit) {
        setFlashMessage('Audit not found.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Check access: Admin can view any, Auditor only assigned audits
    if ($user['role'] === 'Auditor' && $audit['auditor_id'] != $user['id']) {
        setFlashMessage('You can only view reports for audits assigned to you.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Staff cannot access reports
    if ($user['role'] === 'Staff') {
        setFlashMessage('You do not have permission to view audit reports.', 'danger');
        redirect('/modules/audits/list.php');
    }
    
    // Get checklist items
    $stmt = $pdo->prepare("SELECT * FROM audit_checklist WHERE audit_id = ? ORDER BY id");
    $stmt->execute([$audit_id]);
    $checklist_items = $stmt->fetchAll();
    
    // Get checklist statistics
    $stmt = $pdo->prepare("SELECT response, COUNT(*) as count FROM audit_checklist WHERE audit_id = ? GROUP BY response");
    $stmt->execute([$audit_id]);
    $checklist_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_checklist = count($checklist_items);
    $checklist_yes = $checklist_stats['Yes'] ?? 0;
    $checklist_no = $checklist_stats['No'] ?? 0;
    $checklist_na = $checklist_stats['N/A'] ?? 0;
    
    // Get findings
    $stmt = $pdo->prepare("SELECT f.*, u.full_name as responsible_name FROM findings f LEFT JOIN users u ON f.responsible_user_id = u.id WHERE f.audit_id = ? ORDER BY f.created_at");
    $stmt->execute([$audit_id]);
    $findings = $stmt->fetchAll();
    
    // Get findings statistics
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM findings WHERE audit_id = ? GROUP BY status");
    $stmt->execute([$audit_id]);
    $findings_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_findings = count($findings);
    $findings_open = $findings_stats['Open'] ?? 0;
    $findings_resolved = $findings_stats['Resolved'] ?? 0;
    $findings_closed = $findings_stats['Closed'] ?? 0;
    
    // Get documents
    $stmt = $pdo->prepare("SELECT d.*, u.full_name as uploaded_by_name FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id WHERE d.audit_id = ? ORDER BY d.upload_date");
    $stmt->execute([$audit_id]);
    $documents = $stmt->fetchAll();
    
    $total_documents = count($documents);
    
} catch (PDOException $e) {
    setFlashMessage('Error fetching audit data: ' . $e->getMessage(), 'danger');
    redirect('/modules/audits/list.php');
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

// Define status badge colors
$status_colors = [
    'Planned' => 'bg-secondary',
    'In Progress' => 'bg-warning',
    'Completed' => 'bg-success'
];

// Define severity colors
$severity_colors = [
    'High' => 'badge-inactive',
    'Medium' => 'bg-warning',
    'Low' => 'badge-active'
];

// Define finding status colors
$finding_status_colors = [
    'Open' => 'badge-inactive',
    'Resolved' => 'badge-active',
    'Closed' => 'bg-secondary'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Report - <?php echo htmlspecialchars($audit['audit_code']); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f9fafb;
            color: #374151;
        }
        
        .report-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .report-header {
            border-bottom: 2px solid #1f2937;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .report-header h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .report-header .meta {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-table th {
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
            text-align: left;
            padding: 10px;
        }
        
        .info-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .data-table th {
            background-color: #1f2937;
            color: white;
            text-align: left;
            padding: 12px;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .summary-box {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .comments-box {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #0d9488;
        }
        
        .comments-box h4 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .comments-text {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .status-draft {
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #1f2937;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-btn:hover {
            background-color: #374151;
        }
        
        @media print {
            .print-btn {
                display: none;
            }
            
            body {
                background-color: white;
            }
            
            .report-container {
                box-shadow: none;
                margin: 0;
                padding: 20px;
                max-width: 100%;
            }
            
            .info-table th,
            .data-table th {
                background-color: #f9fafb !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        <i class="bi bi-printer"></i> Print
    </button>
    
    <div class="report-container">
        <div class="report-header">
            <h1>Audit Report</h1>
            <div class="meta">
                <strong><?php echo APP_NAME; ?></strong> | Generated: <?php echo date('F j, Y, g:i A'); ?>
            </div>
        </div>
        
        <!-- Audit Details -->
        <div class="section">
            <h2>Audit Details</h2>
            <table class="info-table">
                <tr>
                    <th>Audit Code</th>
                    <td><?php echo htmlspecialchars($audit['audit_code']); ?></td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td><?php echo htmlspecialchars($audit['title']); ?></td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?php echo htmlspecialchars($audit['department']); ?></td>
                </tr>
                <tr>
                    <th>Auditor</th>
                    <td><?php echo htmlspecialchars($audit['auditor_name'] ?? 'Unassigned'); ?></td>
                </tr>
                <tr>
                    <th>Audit Date</th>
                    <td><?php echo formatDate($audit['audit_date']); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if ($audit['status'] === 'Completed'): ?>
                            <span class="status-completed">Completed</span>
                        <?php else: ?>
                            <span class="status-draft">DRAFT - <?php echo htmlspecialchars($audit['status']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($audit['reviewed_by']): ?>
                <tr>
                    <th>Reviewed By</th>
                    <td><?php echo htmlspecialchars($audit['reviewed_by_name']); ?></td>
                </tr>
                <tr>
                    <th>Reviewed At</th>
                    <td><?php echo formatDate($audit['reviewed_at'], 'F j, Y, g:i A'); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            
            <?php if ($audit['description']): ?>
            <div class="mt-3">
                <strong>Description:</strong>
                <div class="mt-2"><?php echo nl2br(htmlspecialchars($audit['description'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Checklist Summary -->
        <div class="section">
            <h2>Checklist Results</h2>
            <div class="summary-box">
                <strong>Summary:</strong> <?php echo $total_checklist; ?> total questions
                <br>
                <span class="badge badge-active">Yes: <?php echo $checklist_yes; ?></span>
                <span class="badge badge-inactive">No: <?php echo $checklist_no; ?></span>
                <span class="badge bg-secondary">N/A: <?php echo $checklist_na; ?></span>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Response</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checklist_items as $index => $item): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['question_text']); ?></td>
                        <td>
                            <?php if ($item['response']): ?>
                                <span class="badge <?php echo $item['response'] === 'Yes' ? 'badge-active' : ($item['response'] === 'No' ? 'badge-inactive' : 'bg-secondary'); ?>">
                                    <?php echo htmlspecialchars($item['response']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['note'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Findings -->
        <div class="section">
            <h2>Findings</h2>
            <div class="summary-box">
                <strong>Summary:</strong> <?php echo $total_findings; ?> total findings
                <br>
                <span class="badge badge-inactive">Open: <?php echo $findings_open; ?></span>
                <span class="badge badge-active">Resolved: <?php echo $findings_resolved; ?></span>
                <span class="badge bg-secondary">Closed: <?php echo $findings_closed; ?></span>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Responsible Person</th>
                        <th>Status</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($findings as $f): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f['finding_title']); ?></td>
                        <td>
                            <span class="badge <?php echo $severity_colors[$f['severity']]; ?>">
                                <?php echo htmlspecialchars($f['severity']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($f['responsible_name'] ?? 'Unassigned'); ?></td>
                        <td>
                            <span class="badge <?php echo $finding_status_colors[$f['status']]; ?>">
                                <?php echo htmlspecialchars($f['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $f['due_date'] ? formatDate($f['due_date']) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Documents -->
        <?php if ($total_documents > 0): ?>
        <div class="section">
            <h2>Uploaded Documents</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document Name</th>
                        <th>Type</th>
                        <th>Uploaded By</th>
                        <th>Upload Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                        <td><?php echo strtoupper(htmlspecialchars($doc['file_type'])); ?></td>
                        <td><?php echo htmlspecialchars($doc['uploaded_by_name']); ?></td>
                        <td><?php echo formatDate($doc['upload_date']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Auditor Comments -->
        <?php if ($audit['auditor_comments']): ?>
        <div class="section">
            <h2>Auditor Comments</h2>
            <div class="comments-box">
                <h4>Comments:</h4>
                <div class="comments-text"><?php echo nl2br(htmlspecialchars($audit['auditor_comments'])); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Final Remarks -->
        <?php if ($audit['final_remarks']): ?>
        <div class="section">
            <h2>Final Remarks</h2>
            <div class="comments-box">
                <h4>Remarks:</h4>
                <div class="comments-text"><?php echo nl2br(htmlspecialchars($audit['final_remarks'])); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="section" style="margin-bottom: 0;">
            <hr>
            <p class="text-muted text-center mb-0" style="font-size: 0.9rem;">
                Report generated by <?php echo APP_NAME; ?> on <?php echo date('F j, Y, g:i A'); ?>
            </p>
        </div>
    </div>
</body>
</html>
