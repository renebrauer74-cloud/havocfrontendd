<?php
// Vorlage: als config/config.php kopieren und anpassen. config/config.php ist in .gitignore.
return [
    // Leer lassen, wenn public/ der Webroot ist. Bei Installation im Unterordner z.B. '/havoc'.
    'base_url' => '',

    'timezone' => 'Europe/Berlin',

    // true = alle PHP-Fehler (E_ALL) im Browser anzeigen. Nur zur Fehlersuche, in Produktion false!
    'debug' => false,

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
        'max_bytes' => 500 * 1024 * 1024,
    ],

    'database' => [
        // 'mysql' = MariaDB/MySQL (Produktion), 'json' = Dateien in storage/data/ (lokale Entwicklung).
        // Die IONOS-DB ist nur vom IONOS-Webspace aus erreichbar, lokal also 'json' verwenden.
        'driver'   => 'json',
        'host'     => '',
        'port'     => 3306,
        'name'     => '',
        'user'     => '',
        'password' => '',
    ],
];
