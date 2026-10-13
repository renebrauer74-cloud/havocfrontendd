<?php
// Vorlage: als config/config.php kopieren und anpassen. config/config.php ist in .gitignore.
return [
    // Leer lassen, wenn public/ der Webroot ist. Bei Installation im Unterordner z.B. '/havoc'.
    'base_url' => '',

    'timezone' => 'Europe/Berlin',

    'admin' => [
        'username' => 'admin',
        // Neuen Hash erzeugen: php bin/hash-password.php "dein-passwort"
        // Der Hash unten gehört zum lokalen Dev-Passwort "havoc-dev" – VOR DEM DEPLOYMENT ÄNDERN!
        'password_hash' => '$2y$12$hvvucAaM5OXRMfVG7wFPH.Emi7Lg0GtcI/tQ8NBbwHSIsQIFm2dzi',
    ],

    'contact' => [
        // Wenn gesetzt, wird jede Kontaktanfrage zusätzlich per mail() verschickt.
        'mail_to'   => '',
        'mail_from' => '',
    ],

    'uploads' => [
        'max_bytes' => 8 * 1024 * 1024,
    ],

    // TODO: DB-Anbindung folgt – bis dahin liegen die Daten als JSON in storage/data/.
];
