<?php
/* Paiement PayPal vérifié CÔTÉ SERVEUR : le navigateur ne peut jamais « déclarer » qu'il a payé. */
require __DIR__ . '/bootstrap.php';
$pp = $CFG['paypal'] + ['client_id' => '', 'amount' => '297.00', 'currency' => 'USD'];
$pp['client_secret'] = $pp['client_secret'] ?? ($pp['secret'] ?? '');
$pp['client_id'] = trim((string)$pp['client_id']); $pp['client_secret'] = trim((string)$pp['client_secret']);
$action = $_GET['action'] ?? '';
$base = $pp['base_url'] ?? (($pp['mode'] ?? 'sandbox') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com');

if ($action === 'config') out(['clientId' => $pp['client_id'], 'amount' => $pp['amount'], 'currency' => $pp['currency']]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Méthode non autorisée.', 405);
if (!function_exists('curl_init')) fail('L’extension PHP curl n’est pas activée.', 503, ['detail' => 'Dans php.ini, activez extension=curl puis redémarrez Apache/PHP.']);
if ($pp['client_id'] === '' || $pp['client_secret'] === '') fail('PayPal n’est pas configuré.', 503);
$u = require_user(); $in = body();

function pp_call(string $method, string $path, array|object|null $json = null, ?string $token = null): array {
    global $pp, $base;
    $ch = curl_init($base . $path); $h = ['Accept: application/json'];
    if ($token === null) { curl_setopt($ch, CURLOPT_USERPWD, $pp['client_id'] . ':' . $pp['client_secret']); curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials'); }
    else { $h[] = 'Authorization: Bearer ' . $token; $h[] = 'Content-Type: application/json'; if ($json !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json)); }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25]);
    if (!empty($pp['ca_bundle'])) curl_setopt($ch, CURLOPT_CAINFO, $pp['ca_bundle']);          // dev local Windows : chemin de cacert.pem
    if (($pp['verify_ssl'] ?? true) === false) { curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); }  // LOCAL UNIQUEMENT
    $raw = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $err = curl_error($ch); curl_close($ch);
    if ($raw === false) { error_log("[paypal] curl : $err"); fail('PayPal injoignable depuis le serveur.', 502, ['detail' => $err]); }
    return [$code, json_decode($raw, true) ?: []];
}
function pp_detail(array $j): string {
    $d = $j['error_description'] ?? ($j['details'][0]['description'] ?? ($j['message'] ?? ($j['error'] ?? '')));
    return is_string($d) ? mb_substr($d, 0, 200) : '';
}
function pp_fail(string $msg, int $http, array $j): never {
    error_log("[paypal] $msg (HTTP $http) " . json_encode($j, JSON_UNESCAPED_UNICODE));
    fail($msg, 502, ['detail' => pp_detail($j) ?: "HTTP $http"]);
}
function pp_token(): string {
    [$c, $j] = pp_call('POST', '/v1/oauth2/token');
    return ($c === 200 && !empty($j['access_token'])) ? $j['access_token'] : pp_fail('Authentification PayPal refusée : vérifiez Client ID / Secret et le mode (sandbox/live).', $c, $j);
}

if ($action === 'create') {
    if (member_state($u)['paid']) fail('Votre accès est déjà actif.', 409);
    [$c, $j] = pp_call('POST', '/v2/checkout/orders', ['intent' => 'CAPTURE', 'purchase_units' => [[
        'custom_id' => (string)$u['id'], 'description' => 'Formation Éveil Intérieur — 6 semaines + 3 séances de coaching',
        'amount' => ['currency_code' => $pp['currency'], 'value' => $pp['amount']]]]], pp_token());
    if ($c >= 300 || empty($j['id'])) pp_fail('Création de la commande PayPal impossible.', $c, $j);
    db()->prepare('INSERT INTO payments (user_id, paypal_order_id, amount, currency, status, created_at) VALUES (?,?,?,?,?,?)')
        ->execute([$u['id'], $j['id'], $pp['amount'], $pp['currency'], 'CREATED', now()]);
    out(['id' => $j['id']]);
}
if ($action === 'capture') {
    $oid = str($in, 'orderID', 64);
    $s = db()->prepare('SELECT * FROM payments WHERE paypal_order_id = ? AND user_id = ?'); $s->execute([$oid, $u['id']]); $row = $s->fetch();
    if (!$row) fail('Commande inconnue.', 404);                      // inclut les commandes d'un autre membre
    if ($row['status'] === 'COMPLETED') out(['paid' => true] + member_state(current_user()));
    if ($row['status'] !== 'CREATED') fail('Paiement non validé.', 402);
    [$c, $j] = pp_call('POST', '/v2/checkout/orders/' . rawurlencode($oid) . '/capture', new stdClass, pp_token());
    $unit = $j['purchase_units'][0] ?? []; $cap = $unit['payments']['captures'][0] ?? [];
    $ok = $c < 300 && ($j['status'] ?? '') === 'COMPLETED' && ($cap['status'] ?? '') === 'COMPLETED'
        && ($cap['amount']['currency_code'] ?? '') === $pp['currency']
        && abs((float)($cap['amount']['value'] ?? 0) - (float)$pp['amount']) < 0.005   // montant exact
        && (string)($cap['custom_id'] ?? $unit['custom_id'] ?? '') === (string)$u['id'];                    // commande liée à CE membre
    if (!$ok) { error_log('[paypal] capture refusée HTTP ' . $c . ' ' . json_encode($j, JSON_UNESCAPED_UNICODE)); db()->prepare("UPDATE payments SET status = 'FAILED' WHERE id = ?")->execute([$row['id']]); fail('Paiement non validé.', 402); }
    db()->prepare("UPDATE payments SET status = 'COMPLETED', paypal_capture_id = ?, payer_email = ?, paid_at = ? WHERE id = ?")
        ->execute([$cap['id'] ?? null, $j['payer']['email_address'] ?? null, now(), $row['id']]);
    db()->prepare('UPDATE users SET paid_at = ? WHERE id = ? AND paid_at IS NULL')->execute([now(), $u['id']]);
    out(['paid' => true] + member_state(current_user()));
}
fail('Action inconnue.', 404);
