<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /#order'); exit; }
verify_csrf();
if (empty($_POST['terms'])) { http_response_code(422); exit('Please accept the order terms.'); }
$email = trim((string)($_POST['customer_email'] ?? ''));
$name = trim((string)($_POST['customer_name'] ?? ''));
$method = (string)($_POST['preferred_payment'] ?? '');
if (!valid_email($email) || !in_array($method, ['BINANCE','FLEXY'], true) || mb_strlen($name) > 120) {
    http_response_code(422); exit('Invalid form data. Go back and check the fields.');
}
$price = (float) setting('price_dzd', '300');
if ($price <= 0 || $price > 10000000) { http_response_code(503); exit('Orders are temporarily unavailable.'); }
$rawToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);
$code = new_order_code();
$stmt = db()->prepare('INSERT INTO orders (public_code, tracking_token_hash, customer_email, customer_name, preferred_payment, amount_dzd, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$code, $tokenHash, $email, $name !== '' ? $name : null, $method, $price, 'NEW']);
$id = (int)db()->lastInsertId();
add_order_event($id, 'CUSTOMER', 'ORDER_CREATED', 'NEW', 'Order submitted');
header('Location: /track.php?code=' . rawurlencode($code) . '&token=' . rawurlencode($rawToken), true, 303);
exit;
