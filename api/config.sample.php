<?php
// Copiez ce fichier en config.php (jamais commité : il est dans .gitignore) puis remplissez vos valeurs.
return [
  'db' => ['host' => 'localhost', 'name' => 'eveil', 'user' => 'eveil_user', 'pass' => 'CHANGEZ_MOI'],
  'paypal' => [
    'mode' => 'sandbox',                       // 'live' en production
    'client_id' => '',                         // PayPal Developer > Apps & Credentials
    'client_secret' => '',
    // 'base_url' => 'https://api-m.paypal.com',   // facultatif : déduit de 'mode'
    'amount' => '297.00',                      // prix fixé côté serveur (jamais lu depuis le navigateur)
    'currency' => 'USD',
  ],
  'contact_to' => 'contact@eveil-interieur.fr', // destinataire des messages du formulaire ('' = base de données seulement)
  'allowed_origin' => '',                       // seulement si le site et l'API sont sur des domaines différents
];
