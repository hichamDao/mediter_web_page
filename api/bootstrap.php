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
/* Accepte les noms de clés courants (password, username, database…) en plus de host/name/user/pass. */
function db_settings(array $c): array {
    $pick = function (array $keys, $default = '') use ($c) { foreach ($keys as $k) if (isset($c[$k]) && $c[$k] !== null) return $c[$k]; return $default; };
    return ['host' => $pick(['host', 'hostname', 'server'], 'localhost'), 'name' => $pick(['name', 'dbname', 'database', 'db']),
            'user' => $pick(['user', 'username', 'login']), 'pass' => $pick(['pass', 'password', 'passwd', 'pwd']), 'port' => $pick(['port'])];
}
/* Traduit l'erreur technique de MySQL en conseil clair (affiché uniquement si 'debug' => true dans config.php). */
function db_hint(string $m): string {
    if (stripos($m, 'could not find driver') !== false) return "L'extension PHP « pdo_mysql » n'est pas activée : activez-la dans les réglages PHP de l'hébergeur.";
    if (stripos($m, 'using password: NO') !== false && preg_match('/\\[1045\\]|Access denied/i', $m)) return "Aucun mot de passe n'a été envoyé à MySQL : la valeur 'pass' est vide ou absente dans api/config.php. La ligne doit être exactement : 'pass' => 'VOTRE_MOT_DE_PASSE' (entre apostrophes, avec la virgule à la fin). Le serveur et l'utilisateur, eux, sont atteints correctement.";
    if (preg_match('/to database|\[1044\]/i', $m)) return "Accès refusé à CETTE base : soit son nom est inexact (chez la plupart des hébergeurs il est préfixé, ex. d123456_eveil), soit l'utilisateur n'a pas les droits dessus (à lui accorder dans l'administration de la base). Si l'utilisateur et le mot de passe sont corrects, c'est l'une de ces deux causes.";
    if (preg_match('/\[1049\]|Unknown database/i', $m)) return "Nom de base inconnu. Chez la plupart des hébergeurs le nom est préfixé (ex. d123456_eveil) : recopiez-le tel qu'affiché dans l'administration.";
    if (preg_match('/\[1045\]|Access denied for user/i', $m)) return "Utilisateur ou mot de passe refusé. Vérifiez 'user' et 'pass' (copiez-collez-les, sans espace). Chez certains hébergeurs l'utilisateur est préfixé (ex. a123456_nom).";
    if (preg_match('/getaddrinfo|php_network_getaddresses|nodename nor servname|Name or service not known/i', $m)) return "Le nom du serveur 'host' est introuvable. Utilisez exactement l'adresse du serveur MySQL indiquée dans l'administration de votre base (elle n'est souvent pas « localhost » sur un hébergement mutualisé).";
    if (preg_match('/\[2002\]|\[2006\]|Connection refused|timed out|No such file/i', $m)) return "Impossible de joindre le serveur MySQL. Vérifiez 'host' (adresse indiquée par l'hébergeur, pas forcément « localhost ») et éventuellement 'port'.";
    if (preg_match('/\[2054\]|authentication method/i', $m)) return "Méthode d'authentification MySQL non prise en charge par ce PHP : demandez à l'hébergeur ou recréez l'utilisateur avec mysql_native_password.";
    return '';
}
function db(): PDO {
    static $pdo = null; global $CFG;
    if ($pdo) return $pdo;
    $d = db_settings($CFG['db'] ?? []);
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 8];
    $dsn = 'mysql:host=' . trim((string)$d['host']) . ($d['port'] !== '' ? ';port=' . (int)$d['port'] : '') . ';dbname=' . trim((string)$d['name']) . ';charset=utf8mb4';
    $user = trim((string)$d['user']); $pass = (string)$d['pass'];
    try {
        try { $pdo = new PDO($dsn, $user, $pass, $opts); }
        catch (PDOException $e) {   // espace/retour à la ligne invisible collé avec le mot de passe : on réessaie sans
            if ($pass === trim($pass) || !preg_match('/\[1045\]/', $e->getMessage())) throw $e;
            $pdo = new PDO($dsn, $user, trim($pass), $opts);
        }
    } catch (Throwable $e) {
        error_log('[db] ' . $e->getMessage());   // visible dans les journaux d'erreurs de l'hébergeur
        $extra = [];
        if (!empty($CFG['debug'])) $extra = ['detail' => $e->getMessage(), 'hint' => db_hint($e->getMessage()), 'config' => [
            'host' => $d['host'], 'database' => $d['name'], 'user' => $user,
            'passwordLength' => strlen($pass), 'passwordHasEdgeSpace' => $pass !== trim($pass),
            'passwordHasQuoteOrBackslashOrDollar' => (bool)preg_match('/[\'"\\\\$]/', $pass)]];   // jamais le mot de passe lui-même
        fail('Connexion à la base de données impossible.', 500, $extra);
    }
    return $pdo;
}
/* Toute erreur non prévue : réponse JSON propre (jamais de trace PHP exposée), détail seulement en mode debug. */
set_exception_handler(function (Throwable $e) {
    global $CFG; error_log('[api] ' . get_class($e) . ' : ' . $e->getMessage());
    $extra = [];
    if (!empty($CFG['debug'])) {
        $extra['detail'] = $e->getMessage();
        if ($e instanceof PDOException && ($e->getCode() === '42S02' || stripos($e->getMessage(), "doesn't exist") !== false))
            $extra['hint'] = "Tables absentes : importez database/schema.sql dans la base (phpMyAdmin → Importer), puis database/seed.sql si vous voulez les articles d'exemple.";
    }
    http_response_code(500); echo json_encode(['error' => 'Erreur serveur.'] + $extra, JSON_UNESCAPED_UNICODE); exit;
});
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
