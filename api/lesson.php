<?php
/* Sert le contenu des leçons UNIQUEMENT aux membres ayant payé, et seulement une fois la semaine atteinte. */
require __DIR__ . '/bootstrap.php';
$u = require_user();
$st = member_state($u);
if (!$st['paid']) fail('Paiement requis', 402, ['code' => 'payment_required']);
$data = json_decode((string)file_get_contents(__DIR__ . '/private/lessons.json'), true) ?: fail('Contenu indisponible', 500);

if (isset($_GET['c'])) {
    $n = (int)$_GET['c'];
    if ($n < 1 || $n > 3) fail('Séance inconnue', 404);
    if ($st['currentWeek'] < COACHING_WEEKS[$n - 1]) fail('Séance pas encore débloquée', 403, ['code' => 'locked', 'week' => COACHING_WEEKS[$n - 1]]);
    out(['lessons' => [$data['coaching'][$n - 1]]]);
}
$n = (int)($_GET['m'] ?? 0);
if ($n < 1 || $n > 6) fail('Module inconnu', 404);
if ($st['currentWeek'] < $n) fail('Module pas encore débloqué', 403, ['code' => 'locked', 'week' => $n]);
out(['lessons' => $data['modules'][$n]]);
