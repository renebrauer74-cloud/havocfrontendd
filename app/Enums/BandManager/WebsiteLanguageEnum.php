<?php

namespace App\Enums\BandManager;

/**
 * Sprache der Band-Website: bestimmt die Biografie aus dem Bandprofil und die Texte (lang/{de,en}/band-website.php).
 */
enum WebsiteLanguageEnum: string
{
    case De = 'de';
    case En = 'en';

    public function label(): string
    {
        return match ($this) {
            self::De => '🇩🇪 Deutsch',
            self::En => '🇬🇧 English',
        };
    }
}
