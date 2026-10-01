<?php
require __DIR__ . '/bootstrap.php';
$action = $_GET['action'] ?? '';

function session_payload(): array {
    $u = current_user();
    $p = ['loggedIn' => (bool)$u, 'csrf' => $_SESSION['csrf']];
    return $u ? $p + ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']] + member_state($u) : $p + ['paid' => false, 'currentWeek' => 0];
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') out(session_payload());
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Méthode non autorisée.', 405);
$in = body();

if ($action === 'register') {
    $name = str($in, 'name', 120, true); $email = strtolower(str($in, 'email', 190)); $pw = (string)($in['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Adresse email invalide.');
    if (strlen($pw) < 8) fail('Le mot de passe doit contenir au moins 8 caractères.');
    $s = db()->prepare('SELECT 1 FROM users WHERE email = ?'); $s->execute([$email]);
    if ($s->fetch()) fail('Un compte existe déjà avec cet email.', 409);
    db()->prepare('INSERT INTO users (name, email, password_hash, created_at) VALUES (?,?,?,?)')->execute([$name, $email, password_hash($pw, PASSWORD_BCRYPT), now()]);
    $_SESSION['uid'] = (int)db()->lastInsertId(); session_regenerate_id(true);
    out(session_payload(), 201);
}
if ($action === 'login') {
    $email = strtolower(str($in, 'email', 190)); $pw = (string)($in['password'] ?? '');
    $c = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND email = ? AND created_at > ?');
    $c->execute([client_ip(), $email, gmdate('Y-m-d H:i:s', time() - 900)]);
    if ((int)$c->fetchColumn() >= 8) fail('Trop de tentatives. Réessayez dans 15 minutes.', 429);
    $s = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?'); $s->execute([$email]); $u = $s->fetch();
    if (!$u || !password_verify($pw, $u['password_hash'])) {
        db()->prepare('INSERT INTO login_attempts (ip, email, created_at) VALUES (?,?,?)')->execute([client_ip(), $email, now()]);
        fail('Email ou mot de passe incorrect.', 401);
    }
    db()->prepare('DELETE FROM login_attempts WHERE ip = ? AND email = ?')->execute([client_ip(), $email]);
    $_SESSION['uid'] = (int)$u['id']; session_regenerate_id(true);
    out(session_payload());
}
if ($action === 'logout') { unset($_SESSION['uid']); session_regenerate_id(true); out(['ok' => true]); }
fail('Action inconnue.', 404);
