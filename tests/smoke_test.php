<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$checks = [];
$pdo = getDatabaseConnection();

$requiredTables = [
    'users', 'departments', 'buildings', 'categories', 'lost_items',
    'found_items', 'claims', 'returns', 'notifications',
];
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($requiredTables as $table) {
    $checks["table:{$table}"] = in_array($table, $tables, true);
}

$requiredFiles = [
    'index.php', 'login.php', 'register.php', 'dashboard.php', 'profile.php',
    'report_lost.php', 'report_found.php', 'report_details.php', 'edit_report.php',
    'search.php', 'matches.php', 'my_reports.php', 'notifications.php',
    'admin/manage_users.php', 'admin/catalog.php', 'admin/departments.php',
    'admin/analytics.php', 'staff/reports.php', 'staff/inventory.php',
    'staff/claims.php', 'staff/returns.php',
];
foreach ($requiredFiles as $file) {
    $checks["file:{$file}"] = file_exists(__DIR__ . '/../' . $file);
}

$checks['password hashing'] = password_verify('TestPassword123', password_hash('TestPassword123', PASSWORD_DEFAULT));
$checks['database charset'] = $pdo->query("SELECT @@character_set_database")->fetchColumn() === 'utf8mb4';

$failed = array_keys(array_filter($checks, fn (bool $passed): bool => !$passed));
foreach ($checks as $name => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
}

if ($failed !== []) {
    echo PHP_EOL . count($failed) . ' smoke test(s) failed.' . PHP_EOL;
    exit(1);
}

echo PHP_EOL . 'All ' . count($checks) . ' smoke tests passed.' . PHP_EOL;
