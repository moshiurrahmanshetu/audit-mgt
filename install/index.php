<?php
require_once __DIR__ . '/guard.php';

// Clear any existing session to prevent stale data from previous installations
session_unset();
session_destroy();
session_start();

$checks = [
    'php_version' => [
        'name' => 'PHP Version >= 8.0',
        'check' => version_compare(PHP_VERSION, '8.0', '>='),
        'critical' => true
    ],
    'pdo' => [
        'name' => 'PDO Extension',
        'check' => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
        'critical' => true
    ],
    'gd' => [
        'name' => 'GD or FileInfo Extension',
        'check' => extension_loaded('gd') || extension_loaded('fileinfo'),
        'critical' => true
    ],
    'config_writable' => [
        'name' => 'Config Folder Writable',
        'check' => is_writable(__DIR__ . '/../config'),
        'critical' => true
    ],
    'uploads_writable' => [
        'name' => 'Uploads Folder Writable',
        'check' => function() {
            $uploadsDir = __DIR__ . '/../uploads';
            $subdirs = ['avatars', 'documents'];
            
            // Try to create if doesn't exist
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            foreach ($subdirs as $subdir) {
                $path = $uploadsDir . '/' . $subdir;
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
            }
            
            return is_writable($uploadsDir);
        },
        'critical' => true
    ],
    'tmp_writable' => [
        'name' => 'Installer Temp Folder Writable',
        'check' => is_writable(__DIR__ . '/uploads_tmp'),
        'critical' => true
    ]
];

// Run checks
$allPassed = true;
foreach ($checks as $key => $check) {
    if (is_callable($check['check'])) {
        $checks[$key]['result'] = $check['check']();
    } else {
        $checks[$key]['result'] = $check['check'];
    }
    
    if (!$checks[$key]['result'] && $check['critical']) {
        $allPassed = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Audit Management CMS</title>
    <link rel="stylesheet" href="assets/installer.css">
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🛡️ Audit Management CMS Setup</h1>
            <p>Welcome to the installation wizard</p>
        </div>
        
        <div class="progress-steps">
            <div class="step active">
                <span class="step-number">1</span>
                <span>Requirements</span>
            </div>
            <div class="step">
                <span class="step-number">2</span>
                <span>Database</span>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <span>Import</span>
            </div>
            <div class="step">
                <span class="step-number">4</span>
                <span>Admin</span>
            </div>
            <div class="step">
                <span class="step-number">5</span>
                <span>Done</span>
            </div>
        </div>
        
        <div class="installer-body">
            <h2 style="margin-bottom: 20px; color: #f1f5f9;">Server Requirements Check</h2>
            
            <ul class="requirements-list">
                <?php foreach ($checks as $check): ?>
                <li class="requirement-item <?php echo $check['result'] ? 'pass' : 'fail'; ?>">
                    <span class="requirement-icon <?php echo $check['result'] ? 'pass' : 'fail'; ?>">
                        <?php echo $check['result'] ? '✓' : '✗'; ?>
                    </span>
                    <span><?php echo htmlspecialchars($check['name']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!$allPassed): ?>
                <div class="alert alert-error">
                    <strong>⚠️ Critical Requirements Not Met</strong><br>
                    Please fix the issues above before continuing. You may need to:
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <li>Upgrade PHP to version 8.0 or higher</li>
                        <li>Enable PDO and PDO_MYSQL extensions in php.ini</li>
                        <li>Enable GD or FileInfo extension in php.ini</li>
                        <li>Set write permissions on the <code>config</code> and <code>uploads</code> folders (chmod 755)</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <strong>✓ All requirements met!</strong><br>
                    Your server is ready for installation.
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <?php if ($allPassed): ?>
                    <a href="step1.php" class="btn btn-primary btn-block">
                        Continue to Database Setup →
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-block" disabled>
                        Please Fix Requirements First
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="installer-footer">
            Audit Management CMS Installer v1.0
        </div>
    </div>
</body>
</html>
