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
