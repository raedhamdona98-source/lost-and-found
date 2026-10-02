<?php
declare(strict_types=1);

function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $defaults = [
        'host' => '127.0.0.1',
        'database' => 'lost_and_found',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ];
    $localConfigFile = __DIR__ . '/config.local.php';
    $config = file_exists($localConfigFile)
        ? array_merge($defaults, require $localConfigFile)
        : $defaults;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
