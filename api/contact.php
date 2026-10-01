<?php
require __DIR__ . '/bootstrap.php';
$m = $_SERVER['REQUEST_METHOD'];
if ($m === 'GET') { require_admin(); out(['messages' => db()->query('SELECT id, name, email, subject, message, created_at FROM contact_messages ORDER BY id DESC LIMIT 300')->fetchAll()]); }
if ($m === 'DELETE') { require_admin(); db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([(int)($_GET['id'] ?? 0)]); out(['ok' => true]); }
if ($m !== 'POST') fail('Méthode non autorisée.', 405);
$in = body();
if (!empty($in['website'])) out(['ok' => true], 201);          // champ piège anti-spam : un humain ne le remplit pas
$name = str($in, 'name', 120, true); $email = str($in, 'email', 190); $subj = str($in, 'subject', 190); $msg = str($in, 'message', 4000, true);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Adresse email invalide.');
$c = db()->prepare('SELECT COUNT(*) FROM contact_messages WHERE ip = ? AND created_at > ?'); $c->execute([client_ip(), gmdate('Y-m-d H:i:s', time() - 3600)]);
if ((int)$c->fetchColumn() >= 5) fail('Trop de messages envoyés. Réessayez plus tard.', 429);
db()->prepare('INSERT INTO contact_messages (name,email,subject,message,ip,created_at) VALUES (?,?,?,?,?,?)')->execute([$name, $email, $subj, $msg, client_ip(), now()]);
if (!empty($CFG['contact_to'])) {
    $one = fn(string $s) => str_replace(["\r", "\n"], ' ', $s);   // anti injection d'en-têtes
    @mail($CFG['contact_to'], '=?UTF-8?B?' . base64_encode('Contact site : ' . ($one($subj) ?: $one($name))) . '?=', "De : $name <$email>\n\n$msg",
        'Reply-To: ' . $one($email) . "\r\nContent-Type: text/plain; charset=UTF-8");
}
out(['ok' => true], 201);
