<?php
// Common guard for all installer pages
$lockFile = __DIR__ . '/../config/installed.lock';

if (file_exists($lockFile)) {
    // Load constants for BASE_URL (constants.php has no DB dependency, safe to load)
    require_once __DIR__ . '/../config/constants.php';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Already Installed</title>
        <link rel="stylesheet" href="assets/installer.css">
    </head>
    <body>
        <div class="installer-container">
            <div class="installer-header">
                <h1>⚠️ Already Installed</h1>
            </div>
            <div class="installer-body">
                <div class="already-installed">
                    <h2>This application is already installed.</h2>
                    <p>
                        For security reasons, the installer cannot be run on an already-installed system.
                    </p>
                    <p>
                        <strong>To reinstall:</strong>
                    </p>
                    <ol style="text-align: left; color: #94a3b8; font-size: 14px; line-height: 1.8; margin: 0 0 24px 0;">
                        <li>Delete the file: <span class="code-block">config/installed.lock</span></li>
                        <li>Delete the file: <span class="code-block">config/db.php</span></li>
                        <li>Restore a fresh database (drop all tables)</li>
                        <li>Then refresh this page to start a fresh installation</li>
                    </ol>
                    <p>
                        <strong>To use the application:</strong>
                    </p>
                    <p>
                        <a href="<?php echo BASE_URL; ?>/modules/auth/login.php" class="btn btn-primary">Go to Login Page</a>
                    </p>
                </div>
            </div>
            <div class="installer-footer">
                Audit Management CMS Installer
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Start session for installer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
