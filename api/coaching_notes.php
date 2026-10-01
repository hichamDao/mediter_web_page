<?php
/* Carnet de notes de coaching : réservé aux membres ayant payé ; chaque membre ne voit que ses propres notes. */
require __DIR__ . '/bootstrap.php';
$u = require_user(); $st = member_state($u);
if (!$st['paid']) fail('Le carnet est réservé aux membres ayant réglé la formation.', 402);
$m = $_SERVER['REQUEST_METHOD'];
const NOTE_MAX = 20000;

/* Si la table n'existe pas encore (base importée avant cette fonctionnalité), on la crée puis on réessaie. */
function q(string $sql, array $p = []): PDOStatement {
    try { $s = db()->prepare($sql); $s->execute($p); return $s; }
    catch (PDOException $e) {
        if ($e->getCode() !== '42S02') throw $e;
        db()->exec(substr(file_get_contents(__DIR__ . '/../database/add_coaching_notes.sql'), strpos(file_get_contents(__DIR__ . '/../database/add_coaching_notes.sql'), 'CREATE')));
        $s = db()->prepare($sql); $s->execute($p); return $s;
    }
}
function session_no(): int {
    $n = (int)($_GET['session'] ?? 0);
    return ($n >= 1 && $n <= 3) ? $n : fail('Séance invalide (1 à 3).');
}
function unlocked(int $n): bool { global $st; return $st['currentWeek'] >= COACHING_WEEKS[$n - 1]; }

if ($m === 'GET') {
    $rows = q('SELECT session, content, updated_at FROM coaching_notes WHERE user_id = ? ORDER BY session', [$u['id']])->fetchAll();
    $notes = [];
    foreach ($rows as $r) $notes[(string)$r['session']] = ['content' => $r['content'], 'updatedAt' => $r['updated_at']];
    out(['notes' => (object)$notes, 'unlocked' => array_values(array_filter([1, 2, 3], 'unlocked'))]);
}
$n = session_no();
if (!unlocked($n)) fail('Cette séance n’est pas encore débloquée.', 403);
if ($m === 'PUT') {
    $c = str_replace("\r\n", "\n", (string)(body()['content'] ?? ''));
    if (mb_strlen($c) > NOTE_MAX) fail('Note trop longue (' . NOTE_MAX . ' caractères maximum).', 413);
    if (trim($c) === '') { q('DELETE FROM coaching_notes WHERE user_id = ? AND session = ?', [$u['id'], $n]); out(['ok' => true, 'updatedAt' => null]); }
    $t = now();
    q('INSERT INTO coaching_notes (user_id, session, content, created_at, updated_at) VALUES (?,?,?,?,?)
       ON DUPLICATE KEY UPDATE content = ?, updated_at = ?', [$u['id'], $n, $c, $t, $t, $c, $t]);
    out(['ok' => true, 'updatedAt' => $t]);
}
if ($m === 'DELETE') { q('DELETE FROM coaching_notes WHERE user_id = ? AND session = ?', [$u['id'], $n]); out(['ok' => true]); }
fail('Méthode non autorisée.', 405);
