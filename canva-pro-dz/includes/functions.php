<?php
declare(strict_types=1);

function setting(string $key, string $default = ''): string {
    $stmt = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}
function add_order_event(int $orderId, string $actor, string $event, ?string $status = null, ?string $note = null): void {
    $stmt = db()->prepare('INSERT INTO order_events (order_id, actor_type, event_key, status, note) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$orderId, $actor, $event, $status, $note]);
}
function new_order_code(): string {
    return 'CPDZ-' . strtoupper(bin2hex(random_bytes(4)));
}
function valid_email(string $email): bool {
    return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
