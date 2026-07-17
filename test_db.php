<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This diagnostics script is disabled for web requests. Run `php test_db.php` from the command line.');
}

require __DIR__ . '/includes/config.php';

echo "Database Connection Test\n";
echo "========================\n";

try {
    $pdo = rgcDb();
    echo "Connection: OK\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo 'Tables found: ' . count($tables) . "\n";

    if (in_array('users', $tables, true)) {
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        echo 'Users table rows: ' . $userCount . "\n";
    }

    echo "Base URL: " . rgcBaseUrl() . "\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Connection failed: " . $e->getMessage() . "\n");
    exit(1);
}
