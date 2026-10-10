<?php
declare(strict_types=1);

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Application is not configured. Copy includes/config.example.php to includes/config.php and configure credentials.');
}
$config = require $configFile;
date_default_timezone_set($config['timezone'] ?? 'Africa/Algiers');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('cpdz_session');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
    'path' => '/',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
