<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
try {
    $row = db()->query("SELECT rate, source, fetched_at FROM exchange_rates WHERE base_currency='DZD' AND quote_currency='USD' ORDER BY fetched_at DESC LIMIT 1")->fetch();
    $stale = !$row || strtotime($row['fetched_at']) < time() - 21600;
    if ($stale) {
        $context = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'header' => "User-Agent: CanvaProDZ/1.0\r\n"]]);
        $raw = @file_get_contents('https://open.er-api.com/v6/latest/DZD', false, $context);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $rate = $data['rates']['USD'] ?? null;
        if (($data['result'] ?? '') === 'success' && is_numeric($rate) && (float)$rate > 0 && (float)$rate < 1) {
            $source = 'open.er-api.com';
            $time = date('Y-m-d H:i:s');
            $ins = db()->prepare('INSERT INTO exchange_rates(base_currency,quote_currency,rate,source,fetched_at) VALUES(?,?,?,?,?)');
            $ins->execute(['DZD','USD',(float)$rate,$source,$time]);
            $row = ['rate'=>(float)$rate,'source'=>$source,'fetched_at'=>$time];
        }
    }
    if (!$row || !is_numeric($row['rate']) || (float)$row['rate'] <= 0) {
        http_response_code(503); echo json_encode(['ok'=>false,'message'=>'Exchange estimate unavailable']); exit;
    }
    echo json_encode([
        'ok'=>true,
        'base'=>'DZD',
        'quote'=>'USD',
        'dzd_to_usd'=>(float)$row['rate'],
        'usdt_estimate'=>(float)$row['rate'],
        'source'=>$row['source'],
        'updated_at'=>$row['fetched_at'],
        'note'=>'Indicative public market-data estimate only; not an official settlement quote. USDT is approximated at 1 USD and may differ.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok'=>false,'message'=>'Exchange estimate unavailable']);
}
