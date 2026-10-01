<?php
declare(strict_types=1);
/* Socle commun de l'API : config, session, CSRF, helpers JSON, accès base de données. */
const COACHING_WEEKS = [2, 4, 6];   // semaine de déblocage des séances de coaching 1, 2, 3
const TOTAL_WEEKS = 6;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (!is_file(__DIR__ . '/config.php')) { http_response_code(500); exit('{"error":"Serveur non configuré (api/config.php manquant)."}'); }
$CFG = require __DIR__ . '/config.php';

/* CORS facultatif (si le site et l'API sont sur des domaines différents) */
if (!empty($CFG['allowed_origin']) && ($_SERVER['HTTP_ORIGIN'] ?? '') === $CFG['allowed_origin']) {
    header('Access-Control-Allow-Origin: ' . $CFG['allowed_origin']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
}

session_name('eveil_sid');
session_set_cookie_params(['lifetime' => 2592000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));

function out($data, int $code = 200): never { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $code = 400, array $extra = []): never { out(['error' => $msg] + $extra, $code); }
function now(): string { return gmdate('Y-m-d H:i:s'); }          // toutes les dates sont stockées en UTC
function body(): array { $j = json_decode((string)file_get_contents('php://input'), true); return is_array($j) ? $j : []; }
function str(array $b, string $k, int $max, bool $required = false): string {
    $v = mb_substr(trim((string)($b[$k] ?? '')), 0, $max);
    if ($required && $v === '') fail("Le champ « $k » est obligatoire.");
    return $v;
}
function client_ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); }
function db(): PDO {
    static $pdo = null; global $CFG;
    if ($pdo) return $pdo;
    $d = $CFG['db'];
    try {
        $pdo = new PDO("mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4", $d['user'], $d['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    } catch (Throwable $e) { fail('Connexion à la base de données impossible.', 500); }
    return $pdo;
}
/* Protection CSRF : toute requête qui modifie des données doit renvoyer le jeton de session */
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) {
    if (!hash_equals($_SESSION['csrf'], (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) fail('Jeton de sécurité invalide, rechargez la page.', 403);
}
function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $s = db()->prepare('SELECT id, name, email, role, paid_at FROM users WHERE id = ?'); $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}
function require_user(): array { return current_user() ?? fail('Connexion requise.', 401); }
function require_admin(): array { $u = require_user(); if ($u['role'] !== 'admin') fail('Accès réservé à l’administrateur.', 403); return $u; }
/* Accès : seul un paiement PayPal validé côté serveur renseigne users.paid_at. La semaine courante part de cette date. */
function member_state(array $u): array {
    if ($u['role'] === 'admin') return ['paid' => true, 'currentWeek' => TOTAL_WEEKS, 'daysElapsed' => 0];
    if (empty($u['paid_at'])) return ['paid' => false, 'currentWeek' => 0, 'daysElapsed' => 0];
    $days = max(0, (int)floor((time() - (new DateTime($u['paid_at'], new DateTimeZone('UTC')))->getTimestamp()) / 86400));
    return ['paid' => true, 'currentWeek' => min(intdiv($days, 7) + 1, TOTAL_WEEKS), 'daysElapsed' => $days];
}
