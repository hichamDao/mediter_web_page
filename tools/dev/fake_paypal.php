<?php
// Faux serveur PayPal pour tester en local (php -S 127.0.0.1:8081 tools/dev/fake_paypal.php). NE JAMAIS déployer.
header('Content-Type: application/json');
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$f = sys_get_temp_dir() . '/fakepp.json'; $db = is_file($f) ? json_decode(file_get_contents($f), true) : [];
$mode = getenv('FAKE_PP_MODE') ?: (is_file(sys_get_temp_dir() . '/fakepp.mode') ? trim(file_get_contents(sys_get_temp_dir() . '/fakepp.mode')) : 'ok');
if ($p === '/v1/oauth2/token') { echo json_encode(['access_token' => 'FAKE']); exit; }
if ($p === '/v2/checkout/orders') {
    $b = json_decode(file_get_contents('php://input'), true); $id = 'ORD' . bin2hex(random_bytes(5));
    $db[$id] = $b['purchase_units'][0]; file_put_contents($f, json_encode($db)); echo json_encode(['id' => $id, 'status' => 'CREATED']); exit;
}
if (preg_match('#^/v2/checkout/orders/([^/]+)/capture$#', $p, $m)) {
    $pu = $db[$m[1]] ?? null; if (!$pu) { http_response_code(404); echo '{}'; exit; }
    $val = $mode === 'wrong_amount' ? '1.00' : $pu['amount']['value'];
    $status = $mode === 'declined' ? 'DECLINED' : 'COMPLETED';
    echo json_encode(['id' => $m[1], 'status' => $status, 'payer' => ['email_address' => 'buyer@example.com'],
        'purchase_units' => [['reference_id' => 'default', 'payments' => ['captures' => [['id' => 'CAP' . $m[1], 'status' => $status, 'custom_id' => $pu['custom_id'],
        'amount' => ['currency_code' => $pu['amount']['currency_code'], 'value' => $val]]]]]]]); exit;
}
http_response_code(404); echo '{}';
