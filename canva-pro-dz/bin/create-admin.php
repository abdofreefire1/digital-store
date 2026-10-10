<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found.\n");
}
require dirname(__DIR__) . '/includes/bootstrap.php';

$username = trim((string)($argv[1] ?? ''));
if ($username === '' || strlen($username) > 80 || !preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
    fwrite(STDERR, "Usage: php bin/create-admin.php <username>\nUsername: 1-80 chars; letters, numbers, dot, underscore, hyphen.\n");
    exit(2);
}
$interactive = function_exists('stream_isatty') && stream_isatty(STDIN);
if ($interactive && DIRECTORY_SEPARATOR === '/') {
    fwrite(STDOUT, "New administrator password (minimum 14 characters): ");
    @exec('stty -echo');
    $password = rtrim((string)fgets(STDIN), "\r\n");
    @exec('stty echo');
    fwrite(STDOUT, "\nConfirm password: ");
    @exec('stty -echo');
    $confirm = rtrim((string)fgets(STDIN), "\r\n");
    @exec('stty echo');
    fwrite(STDOUT, "\n");
} else {
    $password = (string)getenv('CPDZ_ADMIN_PASSWORD');
    $confirm = $password;
    if ($password === '') {
        fwrite(STDERR, "Set CPDZ_ADMIN_PASSWORD in your process environment, or run this script in an interactive terminal.\n");
        exit(2);
    }
}
if (strlen($password) < 14 || !hash_equals($password, $confirm)) {
    fwrite(STDERR, "Password confirmation failed or password is too short.\n");
    exit(2);
}
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare('INSERT INTO admins (username, password_hash, active) VALUES (?, ?, 1)');
try {
    $stmt->execute([$username, $hash]);
    fwrite(STDOUT, "Administrator created. Remove the password from your environment if you set it there.\n");
} catch (PDOException $e) {
    fwrite(STDERR, "Could not create administrator (username may already exist or database is unavailable).\n");
    exit(1);
}
