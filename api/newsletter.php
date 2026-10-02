<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/mail.php';
$m = $_SERVER['REQUEST_METHOD']; $action = $_GET['action'] ?? '';

/* Si la table n'existe pas encore (base importée avant cette fonctionnalité), on la crée puis on réessaie. */
function nl(string $sql, array $p = []): PDOStatement {
    try { $s = db()->prepare($sql); $s->execute($p); return $s; }
    catch (PDOException $e) {
        if ($e->getCode() !== '42S02') throw $e;
        $f = file_get_contents(__DIR__ . '/../database/add_newsletter.sql'); db()->exec(substr($f, strpos($f, 'CREATE')));
        $s = db()->prepare($sql); $s->execute($p); return $s;
    }
}
if ($m === 'GET') {                                   // liste réservée à l'administrateur
    require_admin();
    $rows = nl('SELECT email, first_name, created_at, unsubscribed_at FROM newsletter_subscribers ORDER BY id DESC')->fetchAll();
    if (($_GET['format'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="newsletter.csv"'); header_remove('Cache-Control');
        $safe = fn($v) => preg_match('/^[=+\-@\t\r]/', (string)$v) ? "'" . $v : (string)$v;   // anti injection de formules Excel
        $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['email', 'prénom', 'inscrit le', 'désinscrit le'], ';');
        foreach ($rows as $r) fputcsv($o, [$safe($r['email']), $safe($r['first_name']), $r['created_at'], $r['unsubscribed_at']], ';');
        exit;
    }
    out(['subscribers' => $rows, 'active' => count(array_filter($rows, fn($r) => !$r['unsubscribed_at']))]);
}
if ($m !== 'POST') fail('Méthode non autorisée.', 405);
$in = body();

if ($action === 'unsubscribe') {
    $t = str($in, 'token', 64);
    $s = nl('SELECT id FROM newsletter_subscribers WHERE token = ?', [$t]);
    if (!$s->fetch()) fail('Lien de désinscription invalide.', 404);
    nl('UPDATE newsletter_subscribers SET unsubscribed_at = ? WHERE token = ? AND unsubscribed_at IS NULL', [now(), $t]);
    out(['ok' => true]);
}
if (!empty($in['website'])) out(['ok' => true]);        // champ piège anti-spam
$hits = array_filter($_SESSION['nl_hits'] ?? [], fn($x) => $x > time() - 3600);
if (count($hits) >= 5) fail('Trop de demandes. Réessayez plus tard.', 429);
$_SESSION['nl_hits'] = array_merge($hits, [time()]);
$email = strtolower(str($in, 'email', 190)); $first = str($in, 'first_name', 120);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Adresse email invalide.');
$row = nl('SELECT id, token, unsubscribed_at FROM newsletter_subscribers WHERE email = ?', [$email])->fetch();
$token = null;
if (!$row) { $token = bin2hex(random_bytes(16)); nl('INSERT INTO newsletter_subscribers (email, first_name, token, created_at) VALUES (?,?,?,?)', [$email, $first, $token, now()]); }
elseif ($row['unsubscribed_at']) { $token = $row['token']; nl('UPDATE newsletter_subscribers SET unsubscribed_at = NULL WHERE id = ?', [$row['id']]); }
if ($token) {   // nouvel inscrit (ou réinscription) : email de bienvenue avec lien de désinscription
    send_mail($email, 'Bienvenue dans la newsletter Éveil Intérieur',
"Bonjour,

Merci de vous être inscrit(e) à la newsletter Éveil Intérieur. Vous recevrez de temps en temps nos conseils et inspirations.

Se désinscrire à tout moment : " . site_url() . "/newsletter-desinscription.html?token=$token

À bientôt,
L'équipe Éveil Intérieur");
}
out(['ok' => true, 'message' => 'Merci, votre inscription est enregistrée.'], 201);   // même réponse si déjà inscrit : on ne révèle pas l'existence d'une adresse
