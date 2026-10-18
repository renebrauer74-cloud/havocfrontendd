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

    // Rollen: App\Enums\BandManager\UserRoleEnum – Schlüssel von 'permissions'
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
            'logins.manage',        // Zugänge für Bandmitglieder, Team und Fotografen
            'riders.view',          // Rider Creator (Tech & Catering Rider) ansehen & als PDF laden
            'riders.edit',          // Rider Creator bearbeiten
        ],

        // Zugänge einer Band: Rechte kommen aus den freigegebenen Bereichen (App\Enums\BandManager\BandAreaEnum)
        'band_member' => [],
        'team' => [],
        'photographer' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Verfügbarkeiten (Ampel-Kalender)
    |--------------------------------------------------------------------------
    */

    'availability' => [
        // Status: App\Enums\BandManager\AvailabilityStatusEnum
        'max_range_days' => 366,   // max. Zeitraum pro Speichervorgang
        'summary_days' => 90,      // Zeitraum für die Dashboard-Übersicht
    ],

    /*
    |--------------------------------------------------------------------------
    | Gigs / Bookings & Day Sheet
    |--------------------------------------------------------------------------
    */

    'gigs' => [
        // Status: App\Enums\BandManager\GigStatusEnum
        'block_availability' => true,   // bestätigte Gigs setzen den Tag im Kalender automatisch auf Rot

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
        'max_megapixels' => 60, // größere Fotos werden abgelehnt – die Verkleinerung braucht sonst zu viel RAM
        'bio_max' => 10000,     // Zeichen pro Sprache im Bandprofil

        // Crew-Aufgaben: App\Enums\BandManager\CrewRoleEnum
        'shirt_sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'],

        // Social Media & Streaming: App\Enums\BandManager\PlatformEnum
    ],

    /*
    |--------------------------------------------------------------------------
    | Bildergalerie
    |--------------------------------------------------------------------------
    */

    'gallery' => [
        'max_images' => 8,          // Bilder pro Band
        'max_photo_mb' => 50,       // pro Bild
        'max_megapixels' => 60,     // größere Fotos werden abgelehnt – die Vorschau braucht sonst zu viel RAM
        'preview_px' => 1600,       // Vorschau (Web + PDF), das Original bleibt erhalten
        // Arten: App\Enums\BandManager\GalleryCategoryEnum
    ],

    /*
    |--------------------------------------------------------------------------
    | Dokumente (Verträge & Rider)
    |--------------------------------------------------------------------------
    */

    'documents' => [
        'max_size_mb' => 50,   // Verträge & Rider (PDF)
        // Rider-Arten: App\Enums\BandManager\RiderCategoryEnum
    ],

    /*
    |--------------------------------------------------------------------------
    | Rider Creator – Tech Rider
    |--------------------------------------------------------------------------
    |
    | Die Vorlagen fügt die Band per Klick in ihre Listen ein und passt sie dann
    | an (Zeilen löschen, ändern, eigene ergänzen). Stative, Monitor-Arten und
    | „Wer stellt?“: App\Enums\BandManager\MicStandEnum, MonitorTypeEnum,
    | BacklineProviderEnum.
    |
    */

    'tech_rider' => [
        'max_stage_plot_mb' => 20,  // Bühnenplan (PNG/JPG)
        'max_rows' => 96,           // Zeilen pro Liste
        'text_max' => 5000,         // Zeichen pro Freitext

        'templates' => [
            // Standard-Kanalliste einer Rockband (Drums, Bass, 2 Gitarren, Keys, 3 Stimmen)
            'inputs' => [
                ['source' => 'Kick In', 'mic' => 'Beta 91A', 'stand' => null, 'phantom' => true],
                ['source' => 'Kick Out', 'mic' => 'Beta 52 / D112', 'stand' => 'short_boom', 'phantom' => false],
                ['source' => 'Snare Top', 'mic' => 'SM57', 'stand' => 'short_boom', 'phantom' => false],
                ['source' => 'Snare Bottom', 'mic' => 'SM57 / e604', 'stand' => 'clamp', 'phantom' => false],
                ['source' => 'Hi-Hat', 'mic' => 'SM81', 'stand' => 'short_boom', 'phantom' => true],
                ['source' => 'Tom 1', 'mic' => 'e604', 'stand' => 'clamp', 'phantom' => false],
                ['source' => 'Tom 2', 'mic' => 'e604', 'stand' => 'clamp', 'phantom' => false],
                ['source' => 'Floor Tom', 'mic' => 'e604', 'stand' => 'clamp', 'phantom' => false],
                ['source' => 'Overhead L', 'mic' => 'C414 / KM184', 'stand' => 'tall_boom', 'phantom' => true],
                ['source' => 'Overhead R', 'mic' => 'C414 / KM184', 'stand' => 'tall_boom', 'phantom' => true],
                ['source' => 'Bass DI', 'mic' => 'DI-Box (aktiv)', 'stand' => null, 'phantom' => true],
                ['source' => 'Bass Mic', 'mic' => 'Beta 52 / MD421', 'stand' => 'short_boom', 'phantom' => false],
                ['source' => 'Gitarre 1', 'mic' => 'SM57 / e906', 'stand' => 'short_boom', 'phantom' => false],
                ['source' => 'Gitarre 2', 'mic' => 'SM57 / e906', 'stand' => 'short_boom', 'phantom' => false],
                ['source' => 'Keys L', 'mic' => 'DI-Box', 'stand' => null, 'phantom' => true],
                ['source' => 'Keys R', 'mic' => 'DI-Box', 'stand' => null, 'phantom' => true],
                ['source' => 'Lead Vocals', 'mic' => 'SM58 / Beta 58', 'stand' => 'tall_boom', 'phantom' => false],
                ['source' => 'Backing Vocals 1', 'mic' => 'SM58', 'stand' => 'tall_boom', 'phantom' => false],
                ['source' => 'Backing Vocals 2', 'mic' => 'SM58', 'stand' => 'tall_boom', 'phantom' => false],
            ],
            'monitors' => [
                ['who' => 'Lead Vocals', 'type' => 'wedge', 'note' => 'Viel Gesang, etwas Gitarre'],
                ['who' => 'Gitarre 1', 'type' => 'wedge', 'note' => ''],
                ['who' => 'Gitarre 2', 'type' => 'wedge', 'note' => ''],
                ['who' => 'Bass', 'type' => 'wedge', 'note' => ''],
                ['who' => 'Keys', 'type' => 'wedge', 'note' => ''],
                ['who' => 'Drums', 'type' => 'drumfill', 'note' => 'Kick, Bass, Gesang'],
            ],
            'backline' => [
                ['item' => 'Drumset: Kick 22", Tom 12", Floor Tom 16", Snare 14", komplette Hardware', 'provider' => 'venue', 'note' => 'Becken bringt der Drummer mit'],
                ['item' => 'Drum-Teppich', 'provider' => 'venue', 'note' => ''],
                ['item' => 'Bass-Amp (z. B. Ampeg SVT) + 8x10" Box', 'provider' => 'venue', 'note' => ''],
                ['item' => 'Gitarren-Amps', 'provider' => 'band', 'note' => ''],
                ['item' => 'Keyboard-Ständer (X-Stand)', 'provider' => 'venue', 'note' => ''],
                ['item' => 'Stromverteiler 230 V an jeder Position', 'provider' => 'venue', 'note' => ''],
            ],
            // Ideen für eigene Abschnitte (ein Klick legt den Abschnitt mit diesem Titel an)
            'section_ideas' => ['Pyro / Effekte', 'Video / Beamer', 'Funkstrecken', 'Nebel / Hazer', 'Merch-Stand', 'Aufnahme / Mitschnitt'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rider Creator – Catering Rider
    |--------------------------------------------------------------------------
    |
    | Reisegruppe und Ernährung kommen per Klick aus „Band/Team“. Mahlzeiten und
    | Buyout: App\Enums\BandManager\CateringMealEnum, CateringBuyoutEnum.
    |
    */

    'catering_rider' => [
        'max_rows' => 96,           // Zeilen pro Liste
        'text_max' => 5000,         // Zeichen pro Freitext
        'max_buyout' => 999,        // Buyout pro Person in Euro

        'templates' => [
            // Typische Getränke für einen Show-Tag (Band mit 4–6 Personen)
            'drinks' => [
                ['item' => 'Stilles Wasser 0,5 l', 'amount' => '24 Flaschen', 'note' => 'gekühlt, davon 6 auf der Bühne'],
                ['item' => 'Mineralwasser mit Kohlensäure 0,5 l', 'amount' => '12 Flaschen', 'note' => 'gekühlt'],
                ['item' => 'Softdrinks (Cola, Cola Zero, Limo)', 'amount' => '12 Flaschen', 'note' => 'gekühlt'],
                ['item' => 'Säfte (Apfel, Orange)', 'amount' => '2 Liter', 'note' => ''],
                ['item' => 'Bier', 'amount' => '1 Kiste', 'note' => 'gekühlt, auch alkoholfrei'],
                ['item' => 'Kaffee & Tee', 'amount' => 'ganztägig', 'note' => 'mit Milch, Hafermilch, Zucker, Honig'],
                ['item' => 'Energy Drinks', 'amount' => '6 Dosen', 'note' => ''],
            ],
            // Ideen für eigene Abschnitte (ein Klick legt den Abschnitt mit diesem Titel an)
            'section_ideas' => ['Hotel', 'Runner / Fahrer', 'Gästeliste', 'WLAN', 'Wäsche', 'Parkplätze', 'Merch-Verkauf'],
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
