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

## Tests locaux (facultatif)
`tools/dev/e2e.py` teste toute l'API (inscription, sécurité, paiement, déblocage, carnet, blog, contact) avec un faux serveur PayPal
(`tools/dev/fake_paypal.php`, **ne jamais déployer**). Voir l'en-tête du script.
