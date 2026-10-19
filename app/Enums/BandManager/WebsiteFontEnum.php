<?php

namespace App\Enums\BandManager;

/**
 * Schriftpaare des Website Creators (Google Fonts): Überschrift + Fließtext.
 */
enum WebsiteFontEnum: string
{
    case Condensed = 'condensed';
    case Display = 'display';
    case Grotesk = 'grotesk';
    case Serif = 'serif';
    case Blackletter = 'blackletter';
    case Marker = 'marker';
    case Mono = 'mono';

    public function label(): string
    {
        return match ($this) {
            self::Condensed => 'Condensed',
            self::Display => 'Poster',
            self::Grotesk => 'Modern',
            self::Serif => 'Elegant',
            self::Blackletter => 'Metal',
            self::Marker => 'Handschrift',
            self::Mono => 'Schreibmaschine',
        };
    }

    public function headingFamily(): string
    {
        return match ($this) {
            self::Condensed => 'Bebas Neue',
            self::Display => 'Anton',
            self::Grotesk => 'Space Grotesk',
            self::Serif => 'Playfair Display',
            self::Blackletter => 'Pirata One',
            self::Marker => 'Permanent Marker',
            self::Mono => 'Space Mono',
        };
    }

    public function bodyFamily(): string
    {
        return match ($this) {
            self::Mono => 'Space Mono',
            self::Serif => 'Source Sans 3',
            default => 'Inter',
        };
    }

    /**
     * Parameter "family=…" für https://fonts.googleapis.com/css2.
     */
    public function googleFontsQuery(): string
    {
        $heading = match ($this) {
            self::Grotesk => 'Space+Grotesk:wght@500;700',
            self::Serif => 'Playfair+Display:ital,wght@0,700;1,700',
            self::Mono => 'Space+Mono:wght@400;700',
            default => str_replace(' ', '+', $this->headingFamily()),
        };

        $body = match ($this) {
            self::Mono => null,
            self::Serif => 'Source+Sans+3:wght@400;600',
            default => 'Inter:wght@400;600',
        };

        return 'family='.$heading.($body !== null ? '&family='.$body : '').'&display=swap';
    }
}
