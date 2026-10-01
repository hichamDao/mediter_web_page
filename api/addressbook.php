<?php
require __DIR__ . '/bootstrap.php';
$u = require_user();
$m = $_SERVER['REQUEST_METHOD'];
$id = (int)($_GET['id'] ?? 0);
function fields(array $b): array {
    $email = str($b, 'email', 190);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Adresse email invalide');
    return [str($b, 'name', 120, true), $email, str($b, 'phone', 40), str($b, 'address', 300), str($b, 'notes', 3000)];
}
// Toutes les requêtes filtrent sur user_id : un membre ne voit jamais le carnet d'un autre.
if ($m === 'GET') {
    $s = db()->prepare('SELECT id,name,email,phone,address,notes FROM address_book WHERE user_id=? ORDER BY name');
    $s->execute([$u['id']]); out(['contacts' => $s->fetchAll()]);
}
if ($m === 'POST') {
    [$n, $e, $p, $a, $no] = fields(body());
    db()->prepare('INSERT INTO address_book (user_id,name,email,phone,address,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$u['id'], $n, $e, $p, $a, $no, now(), now()]);
    out(['id' => (int)db()->lastInsertId()], 201);
}
if (!$id) fail('id requis');
if ($m === 'PUT') {
    [$n, $e, $p, $a, $no] = fields(body());
    $s = db()->prepare('UPDATE address_book SET name=?,email=?,phone=?,address=?,notes=?,updated_at=? WHERE id=? AND user_id=?');
    $s->execute([$n, $e, $p, $a, $no, now(), $id, $u['id']]);
    out(['ok' => true]);
}
if ($m === 'DELETE') { db()->prepare('DELETE FROM address_book WHERE id=? AND user_id=?')->execute([$id, $u['id']]); out(['ok' => true]); }
fail('Méthode non autorisée', 405);
