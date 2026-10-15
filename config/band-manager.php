<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allgemein
    |--------------------------------------------------------------------------
    |
    | Der Band-Manager läuft unter /band-manager mit eigenem Login
    | (Tabelle bm_users, Guard "band-manager").
    |
    */

    'name' => 'Agentur Band-Manager',

    // 'light' oder 'dark' – gilt, solange der Benutzer nichts anderes gewählt hat
    'default_theme' => 'light',

    /*
    |--------------------------------------------------------------------------
    | Rollen & Rechte
    |--------------------------------------------------------------------------
    |
    | Rechte werden als Gate geprüft: @can('gigs.view'), can:gigs.view.
    | '*' erlaubt alles, 'gigs.*' alle Rechte, die mit "gigs." beginnen.
    |
    */

    'roles' => [
        'admin' => 'Administrator / Agentur',
        'artist' => 'Künstler / Band',
    ],

    'permissions' => [
        'admin' => ['*'],
        'artist' => [
            'availability.view',
            'availability.edit',
            'gigs.view',
            'gigs.create',          // eigene Shows inkl. Day Sheet anlegen & bearbeiten
            'documents.view',
            'documents.upload',     // Rider hochladen (Verträge nur Agentur)
            'messages.view',
            'team.view',
            'team.edit',            // Personen, Biografie, Links, Bandlogo
            'gallery.view',
            'gallery.edit',         // Bilder hochladen, Hauptbild festlegen
            'presskit.create',      // Pressekit für die eigene Band
        ],
    ],

];
