<?php
// Test auth_check.php standalone inclusion
echo "Testing auth_check.php standalone inclusion...\n";

try {
    // This should load all dependencies and check authentication
    require_once __DIR__ . '/includes/auth_check.php';
    echo "✅ auth_check.php loaded successfully without errors\n";
} catch (Error $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

unlink(__FILE__);
