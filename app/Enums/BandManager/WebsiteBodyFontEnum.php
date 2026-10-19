<?php

namespace App\Enums\BandManager;

/**
 * Schriften für den Fließtext der Band-Website (Google Fonts).
 */
enum WebsiteBodyFontEnum: string
{
    case Inter = 'inter';
    case SourceSans = 'source-sans';
    case DmSans = 'dm-sans';
    case Karla = 'karla';
    case Lora = 'lora';
    case SpaceMono = 'space-mono';

    public function label(): string
    {
        return match ($this) {
            self::Inter => 'Klar',
            self::SourceSans => 'Freundlich',
            self::DmSans => 'Modern',
            self::Karla => 'Locker',
            self::Lora => 'Buch',
            self::SpaceMono => 'Schreibmaschine',
        };
    }

    public function family(): string
    {
        return match ($this) {
            self::Inter => 'Inter',
            self::SourceSans => 'Source Sans 3',
            self::DmSans => 'DM Sans',
            self::Karla => 'Karla',
            self::Lora => 'Lora',
            self::SpaceMono => 'Space Mono',
        };
    }

    /**
     * Parameter "family=…" für https://fonts.googleapis.com/css2 (ohne &display).
     */
    public function googleFamily(): string
    {
        return 'family='.match ($this) {
            self::Lora => 'Lora:ital,wght@0,400;0,600;1,400',
            self::SpaceMono => 'Space+Mono:wght@400;700',
            default => str_replace(' ', '+', $this->family()).':wght@400;600',
        };
    }
}
