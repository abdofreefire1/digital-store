<?php
// backend/public/index.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Core/Router.php';

// CORS configuration based on Environment
$allowedOrigins = [FRONTEND_URL];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    // If not in allowed list, you can either block or allow specific
    // For local dev, allow localhost. For prod, strictly FRONTEND_URL.
    if (APP_ENV === 'local') {
        header("Access-Control-Allow-Origin: *");
    }
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple autoloader
spl_autoload_register(function ($class) {
    // App\Core\Router => app/Core/Router.php
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// JSON Header for API
header('Content-Type: application/json; charset=utf-8');

use App\Core\Router;

$router = new Router();

// Load routes
require_once __DIR__ . '/../routes/api.php';

// Run router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Remove base path if necessary (e.g., /backend/public)
$basePath = parse_url(APP_URL, PHP_URL_PATH);
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
if ($requestUri === '') $requestUri = '/';

$router->dispatch($_SERVER['REQUEST_METHOD'], $requestUri);
