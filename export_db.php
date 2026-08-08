<?php
// Temporary script to export database for testing
require_once __DIR__ . '/config/db.php';

$tables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$sql = "-- Database Export for Audit Management CMS\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Database: " . DB_NAME . "\n\n";

foreach ($tables as $table) {
    $sql .= "-- Table: $table\n";
    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
    
    // Get CREATE TABLE statement
    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $sql .= $row[1] . ";\n\n";
    
    // Get data
    $stmt = $pdo->query("SELECT * FROM `$table`");
    $columns = $stmt->columnCount();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $values = [];
        foreach ($row as $value) {
            if ($value === null) {
                $values[] = 'NULL';
            } else {
                $values[] = "'" . addslashes($value) . "'";
            }
        }
        $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
    }
    
    $sql .= "\n";
}

file_put_contents(__DIR__ . '/database_export.sql', $sql);
echo "Database exported to database_export.sql";
