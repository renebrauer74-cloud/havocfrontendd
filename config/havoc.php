<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kontaktformular
    |--------------------------------------------------------------------------
    |
    | Jede Anfrage wird in der Tabelle "messages" gespeichert. Ist eine
    | Empfängeradresse gesetzt, geht sie zusätzlich per Mail raus
    | (Absender: MAIL_FROM_ADDRESS, Antwort-an: die Adresse der Anfrage).
    |
    */

    'contact' => [
        'mail_to' => env('CONTACT_MAIL_TO'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bild-Uploads
    |--------------------------------------------------------------------------
    |
    | Maximale Größe in Kilobyte. Muss zu upload_max_filesize / post_max_size
    | in public/.user.ini passen.
    |
    */

    'uploads' => [
        'max_kilobytes' => (int) env('UPLOAD_MAX_KILOBYTES', 500 * 1024),
    ],

];
