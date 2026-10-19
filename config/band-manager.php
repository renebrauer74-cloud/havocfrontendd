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
            'riders.view',          // Rider Creator (Tech & Catering Rider, Stage Plot) ansehen & als PDF laden
            'riders.edit',          // Rider Creator bearbeiten
            'cloud.view',           // Cloud-Speicher ansehen & Dateien herunterladen
            'cloud.edit',           // hochladen, Ordner anlegen, umbenennen, verschieben, Papierkorb
            'cloud.share',          // Freigabe-Links für Externe anlegen & widerrufen
            'website.view',         // Website Creator ansehen
            'website.edit',         // Website gestalten & veröffentlichen
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

        // Location Sheets (PDF, JPG, PNG) – kommen hinten ans Day Sheet
        'location_sheets' => [
            'max_files' => 10,
            'max_size_mb' => 20,
            'image_px' => 2000,   // Bilder werden fürs Day Sheet auf diese Kantenlänge verkleinert
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
    | Cloud-Speicher
    |--------------------------------------------------------------------------
    |
    | Dateien liegen im privaten Bucket "band-manager" unter cloud/<band-id>/.
    | max_file_mb muss zu upload_max_filesize / post_max_size in public/.user.ini
    | passen. Dateien im Papierkorb zählen zum Speicherplatz und werden nach
    | trash_days vom Befehl band-manager:cloud-purge-trash endgültig gelöscht.
    |
    */

    'cloud' => [
        'quota_gb' => 10,           // Speicherplatz pro Band
        'max_file_mb' => 500,       // pro Datei
        'trash_days' => 30,
        'thumbnail_px' => 400,      // Vorschau für Bilder in der Dateiliste
        'max_megapixels' => 60,     // größere Bilder bekommen keine Vorschau – zu viel RAM
        'name_max' => 150,          // Zeichen für Ordner- und Dateinamen
        'zip_max_gb' => 4,          // größere Ordner bitte in Teilen herunterladen – der Download läuft durch PHP
        'link_expiry_days' => [7, 30, 90],  // Auswahl beim Freigabe-Link, dazu „unbegrenzt“; vorgewählt der mittlere Wert
        'link_uploads_per_hour' => 300,     // Uploads pro Stunde und Besucher über einen Upload-Link (Schutz vor Missbrauch)
        'link_upload_types' => ['image/*', 'video/*'],   // Upload-Links ohne „alle Dateitypen“: nur Fotos & Videos

        // Ausführbare Dateien – zusätzlich sperrt die Media Library z. B. php, cgi, asp (FileAdder::$defaultDisallowedExtensions)
        'blocked_extensions' => [
            'exe', 'msi', 'msp', 'com', 'bat', 'cmd', 'scr', 'pif', 'cpl', 'dll', 'sys', 'lnk', 'reg', 'hta', 'msc',
            'vbs', 'vbe', 'js', 'jse', 'wsf', 'wsh', 'ps1', 'psm1', 'sh', 'bash', 'csh', 'ksh', 'run', 'bin', 'jar',
            'apk', 'app', 'gadget', 'scf', 'inf', 'appx', 'msix',
        ],

        // Vorgegebene Ordner jeder Band – Hauptordner sind gesperrt, Unterordner frei änderbar
        'default_folders' => [
            'Fotos' => ['Pressefotos', 'Live', 'Backstage & Tour'],
            'Musik' => ['Releases (Master)', 'Demos', 'Proberaum-Mitschnitte'],
            'Recording-Projekte' => ['DAW-Projekte', 'Stems & Spuren', 'Mixe & Bounces', 'Maps & Presets'],
            'Videos' => ['Musikvideos', 'Live-Mitschnitte', 'Social-Media-Clips'],
            'Artwork & Design' => ['Logos', 'Cover', 'Merch, Poster & Flyer'],
            'Texte & Noten' => ['Lyrics', 'Setlists', 'Noten & Tabs'],
            'Presse & Promo' => [],
            'Organisation' => [],
            'Sonstiges' => [],
        ],
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
    | Rider Creator – Stage Plot Designer
    |--------------------------------------------------------------------------
    |
    | Elemente: App\Enums\BandManager\StagePlotElementEnum. Position (x, y = Mitte),
    | Breite w und Tiefe h in cm ab der linken hinteren Bühnenecke (aus Sicht des
    | Publikums), Drehung in Grad. Das PNG zeichnet der Browser beim Speichern.
    |
    */

    'stage_plot' => [
        'max_items' => 200,         // Elemente pro Plot
        'min_stage_m' => 2,         // Bühnenbreite und -tiefe
        'max_stage_m' => 30,
        'max_image_mb' => 10,       // gezeichnetes PNG
        'text_max' => 5000,         // Zeichen für die Hinweise

        'templates' => [
            // Rockband auf 8 × 6 m: Drums auf Riser, Bass links, Gitarre rechts, Gesang vorne Mitte
            'rock' => [
                ['type' => 'riser', 'x' => 400, 'y' => 110, 'w' => 300, 'h' => 200, 'rotation' => 0, 'label' => ''],
                ['type' => 'drums', 'x' => 400, 'y' => 105, 'w' => 220, 'h' => 179, 'rotation' => 0, 'label' => 'Drums (Riser 3 × 2 m)'],
                ['type' => 'drumfill', 'x' => 600, 'y' => 150, 'w' => 100, 'h' => 58, 'rotation' => 270, 'label' => 'Drumfill'],
                ['type' => 'power', 'x' => 230, 'y' => 40, 'w' => 40, 'h' => 48, 'rotation' => 0, 'label' => ''],
                ['type' => 'bass_amp', 'x' => 110, 'y' => 120, 'w' => 100, 'h' => 70, 'rotation' => 0, 'label' => ''],
                ['type' => 'power', 'x' => 40, 'y' => 200, 'w' => 40, 'h' => 48, 'rotation' => 0, 'label' => ''],
                ['type' => 'guitar_amp', 'x' => 700, 'y' => 290, 'w' => 100, 'h' => 56, 'rotation' => 0, 'label' => ''],
                ['type' => 'power', 'x' => 770, 'y' => 230, 'w' => 40, 'h' => 48, 'rotation' => 0, 'label' => ''],
                ['type' => 'person', 'x' => 170, 'y' => 330, 'w' => 70, 'h' => 70, 'rotation' => 0, 'label' => 'Bass'],
                ['type' => 'person', 'x' => 400, 'y' => 330, 'w' => 70, 'h' => 70, 'rotation' => 0, 'label' => 'Lead Vocals'],
                ['type' => 'person', 'x' => 630, 'y' => 330, 'w' => 70, 'h' => 70, 'rotation' => 0, 'label' => 'Guitar'],
                ['type' => 'microphone', 'x' => 170, 'y' => 430, 'w' => 60, 'h' => 60, 'rotation' => 0, 'label' => ''],
                ['type' => 'microphone', 'x' => 400, 'y' => 430, 'w' => 60, 'h' => 60, 'rotation' => 0, 'label' => ''],
                ['type' => 'microphone', 'x' => 630, 'y' => 430, 'w' => 60, 'h' => 60, 'rotation' => 0, 'label' => ''],
                ['type' => 'wedge', 'x' => 170, 'y' => 530, 'w' => 80, 'h' => 56, 'rotation' => 0, 'label' => 'Mon 1'],
                ['type' => 'wedge', 'x' => 400, 'y' => 530, 'w' => 80, 'h' => 56, 'rotation' => 0, 'label' => 'Mon 2'],
                ['type' => 'wedge', 'x' => 630, 'y' => 530, 'w' => 80, 'h' => 56, 'rotation' => 0, 'label' => 'Mon 3'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Website Builder – Website Creator
    |--------------------------------------------------------------------------
    |
    | Jede Band bekommt eine eigene Website unter /sites/{adresse}. Inhalte kommen
    | live aus dem Band-Manager. Themes, Schriften, Hero-Stile und Abschnitte:
    | App\Enums\BandManager\WebsiteThemeEnum, WebsiteFontEnum,
    | WebsiteHeroStyleEnum, WebsiteSectionEnum.
    |
    */

    'website' => [
        'slug_max' => 60,           // Zeichen für die Adresse
        'tagline_max' => 160,
        'section_title_max' => 60,      // eigene Überschrift eines Abschnitts
        'section_intro_max' => 500,     // eigener Einleitungstext unter der Überschrift
        'imprint_max' => 5000,      // Impressum (Pflicht für Websites in Deutschland)
        'max_shows' => 12,          // kommende bestätigte Gigs auf der Website
        'accent_presets' => ['#e5383b', '#ff2bd6', '#ffc400', '#ff7a2f', '#22c55e', '#06b6d4', '#2563eb', '#a855f7', '#b08d57', '#f5f5f5'],
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
