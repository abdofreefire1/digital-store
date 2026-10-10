<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare('SELECT storage_name,mime_type FROM payment_proofs WHERE id=?');
$stmt->execute([$id]);
$proof = $stmt->fetch();
if (!$proof || !in_array($proof['mime_type'], ['image/jpeg','image/png','application/pdf'], true)) { http_response_code(404); exit('Not found.'); }
$base = rtrim((string)$config['proof_dir'], '/');
$file = $base . '/' . basename((string)$proof['storage_name']);
if (!is_file($file)) { http_response_code(404); exit('Not found.'); }
header('X-Content-Type-Options: nosniff');
header('Content-Type: '.$proof['mime_type']);
header('Content-Disposition: inline; filename="payment-proof-'.$id.'"');
header('Cache-Control: private, no-store');
readfile($file);
