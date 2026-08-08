<?php
require_once __DIR__ . '/guard.php';

header('Content-Type: application/json');

$host = $_POST['host'] ?? '';
$port = $_POST['port'] ?? '3306';
$dbname = $_POST['dbname'] ?? '';
$user = $_POST['user'] ?? '';
$pass = $_POST['pass'] ?? '';

if (empty($host) || empty($dbname) || empty($user)) {
    echo json_encode([
        'success' => false,
        'message' => 'Host, database name, and username are required.'
    ]);
    exit;
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Check if database has existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $warning = null;
    if (!empty($tables)) {
        $warning = "This database already contains " . count($tables) . " table(s). Importing may cause conflicts. We recommend using a fresh, empty database.";
    }
    
    // Store credentials in session for next steps
    $_SESSION['install_db'] = [
        'host' => $host,
        'port' => $port,
        'dbname' => $dbname,
        'user' => $user,
        'pass' => $pass
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Connection successful!',
        'warning' => $warning
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $e->getMessage()
    ]);
}
