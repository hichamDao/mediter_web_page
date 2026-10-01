# Installation — backend PHP + MySQL + PayPal

## Prérequis
Hébergement Apache (mutualisé OK) avec **PHP ≥ 8.1** (extensions `pdo_mysql`, `curl`, `mbstring`), **MySQL/MariaDB**, HTTPS.

## 1. Base de données
```bash
mysql -u USER -p NOM_BASE < database/schema.sql
mysql -u USER -p NOM_BASE < database/seed.sql     # 3 articles de blog de départ (facultatif)
```

## 2. Configuration
```bash
cp api/config.sample.php api/config.php   # puis éditez : base MySQL, clés PayPal, prix
```
`api/config.php` est dans `.gitignore` : ne le commitez jamais.

## 3. PayPal
1. https://developer.paypal.com → *Apps & Credentials* → créez une app **Sandbox**, copiez *Client ID* et *Secret*.
2. Testez avec un compte *Sandbox Personal* (paiement de test), puis passez en `live` (`mode`, `base_url` = `https://api-m.paypal.com`, clés Live).
3. Le **prix est fixé dans `config.php`** ; le déblocage n'a lieu qu'après une capture PayPal validée côté serveur
   (statut COMPLETED, montant, devise, membre). Chaque paiement est tracé dans la table `payments`.

## 4. Compte administrateur (gestion du blog et des messages : `admin.html`)
Créez un compte via `login.html#register`, puis :
```sql
UPDATE users SET role='admin' WHERE email='vous@exemple.com';
```

## 5. Contenu des leçons
Les leçons ne sont **plus publiques** : `python3 tools/build_lessons.py` génère `api/private/lessons.json`,
servi uniquement par `api/lesson.php` aux membres ayant payé (et selon la semaine). À relancer après chaque modification
des dossiers « Module … » / « Vos 3 séances de coaching ».

## 6. Sécurité du serveur
* Le `.htaccess` racine bloque l'accès web aux dossiers de leçons, `database/`, `tools/` et à
  `eveil-interieur-modules-et-coaching.html` (qui contient tout le cours). Sur **nginx**, ajoutez l'équivalent :
  ```nginx
  location ~ ^/(Module|Vos|database|tools|\.git|api/private|api/config) { deny all; }
  location = /eveil-interieur-modules-et-coaching.html { deny all; }
  ```
* Servez le site en **HTTPS** (cookie de session `Secure` activé automatiquement).
* Les pages `index/blog/…` sont générées par `tools/build_pages.py` ; les leçons et le blog viennent de MySQL.

## Travailler en local (sans hébergement)
1. Installez **XAMPP** (ou Laragon / MAMP) : PHP ≥ 8.1 + MySQL. Démarrez MySQL, créez la base `eveil` (phpMyAdmin → Importer `database/schema.sql` puis `seed.sql`).
2. `cp api/config.sample.php api/config.php` : renseignez la base (`localhost`, `root`, mot de passe vide avec XAMPP) et vos clés PayPal **Sandbox**.
3. Lancez le site avec PHP (dans le dossier du projet) : `php -S localhost:8000` puis ouvrez http://localhost:8000
   (ne l'ouvrez pas en double-cliquant sur les fichiers ni avec Live Server : sans PHP, l'API ne répond pas).
4. **Diagnostic PayPal** : `php tools/dev/check_paypal.php` indique précisément ce qui bloque (curl, clés, certificat SSL, authentification).
5. Test de paiement : connectez-vous à PayPal avec un compte **Sandbox Personal** (developer.paypal.com → Sandbox → Accounts), pas votre vrai compte.

**Erreur fréquente sous Windows / XAMPP : « SSL certificate problem »** → téléchargez https://curl.se/ca/cacert.pem, enregistrez-le
(ex. `C:/xampp/php/extras/ssl/cacert.pem`) puis ajoutez dans `api/config.php`, section `paypal` : `'ca_bundle' => 'C:/xampp/php/extras/ssl/cacert.pem',`.

## Tests automatisés (facultatif)
`tools/dev/e2e.py` teste toute l'API (inscription, sécurité, paiement, déblocage, carnet, blog, contact) avec un faux serveur PayPal
(`tools/dev/fake_paypal.php`, **ne jamais déployer**). Voir l'en-tête du script.

## Mise à jour d'une base existante
- **Carnet de coaching** : importez `database/add_coaching_notes.sql` (la table est aussi créée automatiquement au premier usage si l'utilisateur MySQL a le droit CREATE).
