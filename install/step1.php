<?php
require_once __DIR__ . '/guard.php';

// Check if we have stored credentials from a previous attempt
$storedCreds = $_SESSION['install_db'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Audit Management CMS</title>
    <link rel="stylesheet" href="assets/installer.css">
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🛡️ Audit Management CMS Setup</h1>
            <p>Step 2 of 5: Database Connection</p>
        </div>
        
        <div class="progress-steps">
            <div class="step completed">
                <span class="step-number">✓</span>
                <span>Requirements</span>
            </div>
            <div class="step active">
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
            <h2 style="margin-bottom: 20px; color: #f1f5f9;">Database Configuration</h2>
            
            <p style="color: #94a3b8; font-size: 14px; margin-bottom: 24px;">
                Please enter your MySQL database credentials. The database should already exist on your server (create it via cPanel/phpMyAdmin if needed).
            </p>
            
            <form id="dbForm">
                <div class="form-group">
                    <label for="db_host">Database Host</label>
                    <input type="text" id="db_host" name="host" value="<?php echo htmlspecialchars($storedCreds['host'] ?? 'localhost'); ?>" required>
                    <div class="form-help">Usually "localhost"</div>
                </div>
                
                <div class="form-group">
                    <label for="db_port">Database Port (Optional)</label>
                    <input type="text" id="db_port" name="port" value="<?php echo htmlspecialchars($storedCreds['port'] ?? '3306'); ?>">
                    <div class="form-help">Default is 3306</div>
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name *</label>
                    <input type="text" id="db_name" name="dbname" value="<?php echo htmlspecialchars($storedCreds['dbname'] ?? ''); ?>" required>
                    <div class="form-help">Name of an EMPTY database you've created</div>
                </div>
                
                <div class="form-group">
                    <label for="db_user">Database Username *</label>
                    <input type="text" id="db_user" name="user" value="<?php echo htmlspecialchars($storedCreds['user'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">Database Password</label>
                    <input type="password" id="db_pass" name="pass" value="<?php echo htmlspecialchars($storedCreds['pass'] ?? ''); ?>">
                    <div class="form-help">Leave empty if no password (local dev)</div>
                </div>
                
                <div id="testResult" style="margin-bottom: 20px;"></div>
                
                <div style="display: flex; gap: 12px; margin-top: 30px;">
                    <button type="button" id="testBtn" class="btn btn-secondary" style="flex: 1;">
                        <span id="testSpinner" style="display: none;" class="loading-spinner"></span>
                        <span id="testText">Test Connection</span>
                    </button>
                    <button type="submit" id="nextBtn" class="btn btn-primary" style="flex: 1;" disabled>
                        Next: Import Database →
                    </button>
                </div>
            </form>
        </div>
        
        <div class="installer-footer">
            <a href="index.php" style="color: #64748b; text-decoration: none;">← Back</a>
        </div>
    </div>
    
    <script src="assets/installer.js"></script>
</body>
</html>
