<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) return $pdo;
    $d = $config['db'];
    if (empty($d['user']) || empty($d['name'])) {
        throw new RuntimeException('Database credentials are missing in includes/config.php.');
    }
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $d['host'], $d['name'], $d['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $d['user'], $d['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
