<?php
declare(strict_types=1);

function e(string|null $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    $provided = (string)($_POST['csrf'] ?? '');
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $provided)) {
        http_response_code(419);
        exit('Session expired. Refresh the page and try again.');
    }
}
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}
function admin_log(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
    $stmt = db()->prepare('INSERT INTO admin_logs (admin_id, action_key, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$_SESSION['admin_id'] ?? null, $action, $entityType, $entityId, $details]);
}
