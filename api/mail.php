<?php
/* Envoi d'emails en texte brut. mode 'mail' = fonction mail() de PHP ; mode 'log' = écrit dans api/private/outbox.log (dev local). */
function site_url(): string {
    global $CFG;
    if (!empty($CFG['site_url'])) return rtrim($CFG['site_url'], '/');   // recommandé : évite de dépendre de l'en-tête Host
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host . rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x.php'))), '/');
}
function mail_clean(string $s): string { return trim(str_replace(["\r", "\n"], ' ', $s)); }   // anti injection d'en-têtes
function send_mail(string $to, string $subject, string $text): bool {
    global $CFG; $m = $CFG['mail'] ?? [];
    $to = mail_clean($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    if (($m['mode'] ?? 'mail') === 'log') {
        return file_put_contents(__DIR__ . '/private/outbox.log', "=== " . now() . " — À : $to\nObjet : $subject\n\n$text\n\n", FILE_APPEND | LOCK_EX) !== false;
    }
    $from = mail_clean($m['from'] ?? ($CFG['contact_to'] ?? '')); if (!filter_var($from, FILTER_VALIDATE_EMAIL)) $from = 'no-reply@localhost';
    $headers = "From: =?UTF-8?B?" . base64_encode('Éveil Intérieur') . "?= <$from>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode(mail_clean($subject)) . '?=', str_replace(["\r\n", "\n"], "\r\n", $text), $headers);
    if (!$ok) error_log("[mail] envoi impossible vers $to (serveur de messagerie non configuré ?)");
    return $ok;
}
function welcome_mail(array $u, string $amount, string $currency): bool {
    $s = site_url();
    return send_mail($u['email'], 'Bienvenue dans Éveil Intérieur : votre accès est ouvert',
"Bonjour {$u['name']},

Merci pour votre confiance. Votre paiement de $amount $currency est bien confirmé et votre accès à la formation est ouvert.

Pour vous connecter :
$s/login.html
Utilisez l'adresse email {$u['email']} et le mot de passe choisi lors de votre inscription.

Comment ça se passe :
- Le module 1 est disponible dès maintenant.
- Un nouveau module s'ouvre chaque semaine, pendant 6 semaines.
- Vos 3 séances de coaching s'ouvrent aux semaines 2, 4 et 6.
- Vous disposez d'un carnet de coaching privé pour noter vos réponses : $s/carnet-coaching.html

Garantie de remboursement de 14 jours : si la formation ne vous convient pas, contactez-nous (voir les conditions : $s/conditions-d-utilisation.html).
Une question ? Écrivez-nous via $s/contact.html

Belle pratique,
L'équipe Éveil Intérieur");
}
