<?php
/* Diagnostic PayPal en ligne de commande :  php tools/dev/check_paypal.php
   Vérifie : extension curl, clés renseignées, connexion à PayPal, authentification, création d'une commande de test. */
$cfgFile = __DIR__ . '/../../api/config.php';
if (!is_file($cfgFile)) exit("✗ api/config.php introuvable (copiez api/config.sample.php)\n");
$c = require $cfgFile; $pp = ($c['paypal'] ?? []) + ['client_id' => '', 'amount' => '297.00', 'currency' => 'USD', 'mode' => 'sandbox'];
$secret = trim((string)($pp['client_secret'] ?? $pp['secret'] ?? '')); $id = trim((string)$pp['client_id']);
$base = $pp['base_url'] ?? ($pp['mode'] === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com');
echo "PHP " . PHP_VERSION . " — mode : {$pp['mode']} — API : $base\n";
if (!function_exists('curl_init')) exit("✗ extension curl absente : activez extension=curl dans php.ini (php --ini pour le trouver) puis relancez.\n");
echo "✓ extension curl\n";
if ($id === '' || $secret === '') exit("✗ client_id ou client_secret vide dans api/config.php\n");
echo "✓ clés renseignées (Client ID : " . substr($id, 0, 6) . "…, " . strlen($id) . " caractères ; Secret : " . strlen($secret) . " caractères)\n";
function call($m, $url, $opts) {
    global $pp; $ch = curl_init($url);
    curl_setopt_array($ch, $opts + [CURLOPT_CUSTOMREQUEST => $m, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    if (!empty($pp['ca_bundle'])) curl_setopt($ch, CURLOPT_CAINFO, $pp['ca_bundle']);
    if (($pp['verify_ssl'] ?? true) === false) { curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); }
    $r = curl_exec($ch); $e = curl_error($ch); $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    return [$r, $e, $code];
}
[$r, $e, $code] = call('POST', "$base/v1/oauth2/token", [CURLOPT_USERPWD => "$id:$secret", CURLOPT_POSTFIELDS => 'grant_type=client_credentials']);
if ($r === false) {
    echo "✗ connexion à PayPal impossible : $e\n";
    if (stripos($e, 'ssl') !== false || stripos($e, 'certificate') !== false)
        echo "  → Windows/XAMPP : téléchargez https://curl.se/ca/cacert.pem, puis dans api/config.php (section paypal) ajoutez :\n    'ca_bundle' => 'C:/chemin/vers/cacert.pem',\n  (ou php.ini : curl.cainfo=\"C:/chemin/vers/cacert.pem\" puis redémarrez).\n";
    exit(1);
}
$j = json_decode($r, true) ?: [];
if ($code !== 200 || empty($j['access_token'])) {
    echo "✗ authentification refusée (HTTP $code) : " . ($j['error_description'] ?? $r) . "\n";
    echo "  → vérifiez que Client ID et Secret viennent de la MÊME app (onglet Sandbox) et que 'mode' => 'sandbox'. Pas d'espace ni de retour à la ligne.\n"; exit(1);
}
echo "✓ authentification PayPal OK\n";
[$r, $e, $code] = call('POST', "$base/v2/checkout/orders", [CURLOPT_POSTFIELDS => json_encode(['intent' => 'CAPTURE', 'purchase_units' => [['amount' => ['currency_code' => $pp['currency'], 'value' => $pp['amount']]]]]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $j['access_token']]]);
$o = json_decode((string)$r, true) ?: [];
if ($code >= 300 || empty($o['id'])) exit("✗ création de commande refusée (HTTP $code) : " . ($o['details'][0]['description'] ?? $o['message'] ?? $r) . "\n");
echo "✓ création de commande OK ({$o['id']}) — tout est bon, le bouton doit fonctionner.\n";
