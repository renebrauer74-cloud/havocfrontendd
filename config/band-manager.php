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

    /*
    |--------------------------------------------------------------------------
    | Verfügbarkeiten (Ampel-Kalender)
    |--------------------------------------------------------------------------
    */

    'availability' => [
        // Status-Schlüssel max. 20 Zeichen
        'statuses' => [
            'green' => ['label' => 'Verfügbar', 'color' => '#22c55e'],
            'orange' => ['label' => 'Muss besprochen werden', 'color' => '#f59e0b'],
            'red' => ['label' => 'Termin nicht möglich', 'color' => '#ef4444'],
        ],
        'max_range_days' => 366,   // max. Zeitraum pro Speichervorgang
        'summary_days' => 90,      // Zeitraum für die Dashboard-Übersicht
    ],

    /*
    |--------------------------------------------------------------------------
    | Gigs / Bookings & Day Sheet
    |--------------------------------------------------------------------------
    */

    'gigs' => [
        'statuses' => [
            'request' => ['label' => 'Anfrage', 'color' => '#8b5cf6'],
            'confirmed' => ['label' => 'Bestätigt', 'color' => '#2563eb'],
            'cancelled' => ['label' => 'Abgesagt', 'color' => '#6b7280'],
        ],
        'block_availability' => true,   // bestätigte Gigs setzen den Tag im Kalender automatisch …
        'block_status' => 'red',        // … auf diesen Ampel-Status

        'catering_options' => [
            'none' => 'Keine Verpflegung vor Ort',
            'catering' => 'Catering vor Ort',
            'buyout' => 'Buy-out',
            'both' => 'Catering + Buy-out',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Band & Team
    |--------------------------------------------------------------------------
    |
    | Upload-Limits müssen zu upload_max_filesize / post_max_size in
    | public/.user.ini passen.
    |
    */

    'team' => [
        'max_photo_mb' => 50,   // Personenfotos (werden auf 800 px verkleinert)
        'max_logo_mb' => 20,    // Bandlogo (wird unverändert gespeichert)
        'photo_px' => 800,
        'bio_max' => 10000,     // Zeichen pro Sprache im Bandprofil

        'crew_roles' => [
            'merch' => 'Merchandise',
            'sound' => 'Tontechnik',
            'monitor' => 'Monitortechnik',
            'light' => 'Lichttechnik',
            'tour_manager' => 'Tourmanagement',
            'driver' => 'Fahrer',
            'backline' => 'Backline / Roadie',
            'other' => 'Sonstiges',
        ],
        'shirt_sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'],

        // Social Media & Streaming – icon = Bootstrap-Icon-Name, handle = Vorlage für "@name"
        'platforms' => [
            'website' => ['label' => 'Website', 'icon' => 'globe2', 'color' => '#6b7280', 'placeholder' => 'https://www.bandname.de'],
            'instagram' => ['label' => 'Instagram', 'icon' => 'instagram', 'color' => '#E4405F', 'placeholder' => '@bandname', 'handle' => 'https://www.instagram.com/%s/'],
            'facebook' => ['label' => 'Facebook', 'icon' => 'facebook', 'color' => '#1877F2', 'placeholder' => 'https://facebook.com/bandname', 'handle' => 'https://www.facebook.com/%s'],
            'tiktok' => ['label' => 'TikTok', 'icon' => 'tiktok', 'color' => '#111111', 'placeholder' => '@bandname', 'handle' => 'https://www.tiktok.com/@%s'],
            'youtube' => ['label' => 'YouTube', 'icon' => 'youtube', 'color' => '#FF0000', 'placeholder' => '@bandname', 'handle' => 'https://www.youtube.com/@%s'],
            'spotify' => ['label' => 'Spotify', 'icon' => 'spotify', 'color' => '#1DB954', 'placeholder' => 'https://open.spotify.com/artist/…'],
            'apple_music' => ['label' => 'Apple Music', 'icon' => 'apple', 'color' => '#FA243C', 'placeholder' => 'https://music.apple.com/…'],
            'amazon_music' => ['label' => 'Amazon Music', 'icon' => 'amazon', 'color' => '#00A8E1', 'placeholder' => 'https://music.amazon.de/artists/…'],
            'deezer' => ['label' => 'Deezer', 'icon' => 'soundwave', 'color' => '#A238FF', 'placeholder' => 'https://www.deezer.com/artist/…'],
            'soundcloud' => ['label' => 'SoundCloud', 'icon' => 'cloud-fill', 'color' => '#FF5500', 'placeholder' => 'https://soundcloud.com/bandname'],
            'bandcamp' => ['label' => 'Bandcamp', 'icon' => 'vinyl-fill', 'color' => '#1DA0C3', 'placeholder' => 'https://bandname.bandcamp.com'],
            'x' => ['label' => 'X (Twitter)', 'icon' => 'twitter-x', 'color' => '#111111', 'placeholder' => '@bandname', 'handle' => 'https://x.com/%s'],
            'threads' => ['label' => 'Threads', 'icon' => 'threads', 'color' => '#111111', 'placeholder' => '@bandname', 'handle' => 'https://www.threads.net/@%s'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bildergalerie
    |--------------------------------------------------------------------------
    */

    'gallery' => [
        'max_images' => 8,          // Bilder pro Band
        'max_photo_mb' => 50,       // pro Bild
        'max_preview_mp' => 150,    // bis 150 Megapixel wird eine Vorschau erzeugt (braucht viel RAM)
        'preview_px' => 1600,       // Vorschau (Web + PDF), das Original bleibt erhalten
        'categories' => [
            'band' => 'Bandfoto',
            'live' => 'Live / Gig',
            'video' => 'Musikvideo (Standbild)',
            'other' => 'Sonstiges',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dokumente (Verträge & Rider)
    |--------------------------------------------------------------------------
    */

    'documents' => [
        'max_size_mb' => 50,   // Verträge & Rider (PDF)
        'rider_categories' => [
            'technical' => 'Technical Rider',
            'catering' => 'Catering Rider',
            'stageplot' => 'Stageplot / Inputliste',
            'other' => 'Sonstiges',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export & Pressekit
    |--------------------------------------------------------------------------
    */

    'exports' => [
        'past_months' => 12,   // "Vergangene Shows" = letzte X Monate

        // Kontakt im Pressekit (Bandinfo-PDF → „Kontakt & Booking“), leere Werte erscheinen nicht
        'contact' => [
            'name' => env('BAND_MANAGER_CONTACT_NAME', 'HAVOC AGENCY'),
            'person' => env('BAND_MANAGER_CONTACT_PERSON', 'Rene Brauer'),
            'email' => env('BAND_MANAGER_CONTACT_EMAIL', 'info@havoc-agency.com'),
            'phone' => env('BAND_MANAGER_CONTACT_PHONE', '+49 177 1695943'),
            'website' => env('BAND_MANAGER_CONTACT_WEBSITE', 'https://havoc-agency.com'),
        ],
    ],

    // Logo für Day Sheet und Pressekit-PDFs (PNG oder JPG)
    'pdf_logo' => public_path('assets/band-manager/img/havoc-logo.png'),

];
