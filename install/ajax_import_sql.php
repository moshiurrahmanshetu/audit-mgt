<?php
require_once __DIR__ . '/guard.php';

// Increase execution time for large imports
set_time_limit(300);

header('Content-Type: application/json');

if (!isset($_SESSION['install_db'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Database credentials not found. Please start over from Step 1.'
    ]);
    exit;
}

$creds = $_SESSION['install_db'];

if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded or upload error occurred.'
    ]);
    exit;
}

$file = $_FILES['sql_file'];

// Validate file type
if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid file type. Please upload a .sql file.'
    ]);
    exit;
}

// Check file size (50MB max)
$maxSize = 50 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode([
        'success' => false,
        'message' => 'File too large. Maximum size is 50MB.'
    ]);
    exit;
}

// Move to temp location
$tempDir = __DIR__ . '/uploads_tmp';
$tempFile = $tempDir . '/' . uniqid('import_', true) . '.sql';

if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save uploaded file.'
    ]);
    exit;
}

try {
    // Connect to database
    $dsn = "mysql:host={$creds['host']};port={$creds['port']};dbname={$creds['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $creds['user'], $creds['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Read SQL file
    $sqlContent = file_get_contents($tempFile);
    
    if ($sqlContent === false) {
        throw new Exception('Failed to read uploaded SQL file.');
    }
    
    // Parse SQL statements
    $statements = parseSqlStatements($sqlContent);
    
    if (empty($statements)) {
        throw new Exception('No valid SQL statements found in the file.');
    }
    
    // Disable foreign key checks (prevents FK ordering errors during import)
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    
    $successCount = 0;
    $totalStatements = count($statements);
    
    // Execute statements individually (no transaction wrapping for DDL due to MySQL implicit commit behavior)
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            // Stop immediately on first failure
            echo json_encode([
                'success' => false,
                'message' => "Import failed at statement " . ($index + 1) . " of $totalStatements. Error: " . $e->getMessage(),
                'failed_statement_preview' => substr($statement, 0, 150),
                'statements_completed_before_failure' => $successCount,
                'warning' => 'Some tables may have already been created before this failure. Please use a fresh, empty database and try again, or manually drop any partially created tables first.'
            ]);
            exit;
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
    
    // Verify tables were created
    $expectedTables = ['users', 'roles', 'audits', 'checklist_templates', 'audit_checklist', 'findings', 'documents', 'activity_log'];
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingTables = array_diff($expectedTables, $existingTables);
    
    if (!empty($missingTables)) {
        throw new Exception('Import completed but some expected tables are missing: ' . implode(', ', $missingTables));
    }
    
    // Success
    echo json_encode([
        'success' => true,
        'message' => "Database imported successfully. $successCount statements executed. " . count($existingTables) . " tables created.",
        'tables_found' => $existingTables
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Clean up temp file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
}

/**
 * Parse SQL file into individual statements
 * Handles quoted strings and comments correctly
 */
function parseSqlStatements($sql) {
    $statements = [];
    $currentStatement = '';
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;
    $escaped = false;
    
    // Normalize line endings
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Skip comment lines
        if (preg_match('/^(--|#)/', $trimmedLine)) {
            continue;
        }
        
        // Skip empty lines
        if ($trimmedLine === '') {
            continue;
        }
        
        // Skip multi-line comment blocks
        if (preg_match('/^\/\*/', $trimmedLine) && preg_match('/\*\/$/', $trimmedLine)) {
            continue;
        }
        
        // Process character by character for proper quote handling
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            $nextChar = $i < strlen($line) - 1 ? $line[$i + 1] : '';
            
            if ($escaped) {
                $currentStatement .= $char;
                $escaped = false;
                continue;
            }
            
            if ($char === '\\' && ($inSingleQuote || $inDoubleQuote)) {
                $currentStatement .= $char;
                $escaped = true;
                continue;
            }
            
            if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
                $inSingleQuote = !$inSingleQuote;
                $currentStatement .= $char;
                continue;
            }
            
            if ($char === '"' && !$inSingleQuote && !$inBacktick) {
                $inDoubleQuote = !$inDoubleQuote;
                $currentStatement .= $char;
                continue;
            }
            
            if ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inBacktick = !$inBacktick;
                $currentStatement .= $char;
                continue;
            }
            
            // Check for statement terminator (semicolon) outside quotes
            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                $currentStatement .= $char;
                $trimmedStatement = trim($currentStatement);
                if (!empty($trimmedStatement)) {
                    $statements[] = $trimmedStatement;
                }
                $currentStatement = '';
                continue;
            }
            
            $currentStatement .= $char;
        }
        
        // Add newline to statement
        $currentStatement .= "\n";
    }
    
    // Add any remaining statement
    $trimmedStatement = trim($currentStatement);
    if (!empty($trimmedStatement)) {
        $statements[] = $trimmedStatement;
    }
    
    return $statements;
}
