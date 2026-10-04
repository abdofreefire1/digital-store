<?php
// backend/config/config.php

function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Load env file if exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

define('APP_ENV', env('APP_ENV', 'production'));
define('APP_URL', env('APP_URL', 'http://localhost/backend/public'));
define('FRONTEND_URL', env('FRONTEND_URL', 'http://localhost:3000'));
define('APP_ROOT', dirname(__DIR__));

// Database config
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_DATABASE', 'digital_store'));
define('DB_USER', env('DB_USERNAME', 'root'));
define('DB_PASS', env('DB_PASSWORD', ''));
define('DB_CHARSET', 'utf8mb4');

// Security
define('JWT_SECRET', env('JWT_SECRET', 'secret'));

// Q8QPay
define('Q8QPAY_API_KEY', env('Q8QPAY_API_KEY', ''));
define('Q8QPAY_WEBHOOK_SECRET', env('Q8QPAY_WEBHOOK_SECRET', ''));
define('Q8QPAY_BASE_URL', env('Q8QPAY_BASE_URL', 'https://q8qpay.com'));

// Resend
define('RESEND_API_KEY', env('RESEND_API_KEY', ''));
define('MAIL_FROM', env('MAIL_FROM', 'noreply@example.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Digital Store'));

// Exchange Rate
define('USDT_RATE_SOURCE', env('USDT_RATE_SOURCE', 'manual'));
define('MANUAL_USDT_RATE', env('MANUAL_USDT_RATE', 250));

date_default_timezone_set('Africa/Algiers');

if (APP_ENV === 'local' || APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
