<?php
// Create a fresh empty database for testing installer
try {
    $pdo = new PDO("mysql:host=localhost", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Drop existing test database if it exists
    $pdo->exec("DROP DATABASE IF EXISTS audit_cms_test");
    
    // Create fresh empty database
    $pdo->exec("CREATE DATABASE audit_cms_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "Test database 'audit_cms_test' created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
