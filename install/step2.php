<?php
require_once __DIR__ . '/guard.php';

// Check if we have database credentials
if (!isset($_SESSION['install_db'])) {
    header('Location: step1.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Database - Audit Management CMS</title>
    <link rel="stylesheet" href="assets/installer.css">
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🛡️ Audit Management CMS Setup</h1>
            <p>Step 3 of 5: Import Database</p>
        </div>
        
        <div class="progress-steps">
            <div class="step completed">
                <span class="step-number">✓</span>
                <span>Requirements</span>
            </div>
            <div class="step completed">
                <span class="step-number">✓</span>
                <span>Database</span>
            </div>
            <div class="step active">
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
            <h2 style="margin-bottom: 20px; color: #f1f5f9;">Import Database File</h2>
            
            <p style="color: #94a3b8; font-size: 14px; margin-bottom: 24px;">
                Upload the complete .sql database file. This file will be imported into the database you configured in the previous step. This may take a few moments for larger files — please do not close this window during import.
            </p>
            
            <div class="alert alert-warning" style="margin-bottom: 24px;">
                <strong>⚠️ Important:</strong> Please ensure you are using a completely <strong>FRESH, EMPTY database</strong> before importing. If the import fails partway through, some tables may have already been created and cannot be automatically rolled back.
            </div>
            
            <form id="importForm">
                <div class="form-group">
                    <label for="sql_file">Database File (.sql) *</label>
                    <input type="file" id="sql_file" name="sql_file" accept=".sql" required>
                    <div class="form-help">Maximum file size: 50MB</div>
                </div>
                
                <div id="importResult" style="margin-bottom: 20px;"></div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" id="importBtn" class="btn btn-primary btn-block">
                        <span id="importSpinner" style="display: none;" class="loading-spinner"></span>
                        <span id="importText">Import Database</span>
                    </button>
                </div>
            </form>
            
            <div id="progressSection" style="display: none; margin-top: 20px;">
                <div class="alert alert-info">
                    <strong>Importing database, please wait...</strong><br>
                    This may take a minute for larger files. Do not close this window.
                </div>
            </div>
        </div>
        
        <div class="installer-footer">
            <a href="step1.php" style="color: #64748b; text-decoration: none;">← Back</a>
        </div>
    </div>
    
    <script src="assets/installer.js"></script>
</body>
</html>
