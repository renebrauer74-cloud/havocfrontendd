<?php

namespace App\Enums\BandManager;

/**
 * Schriften für Überschriften der Band-Website (Google Fonts). Der Fließtext ist eine eigene Wahl
 * (WebsiteBodyFontEnum) – ohne Wahl nimmt die Website defaultBodyFont().
 */
enum WebsiteFontEnum: string
{
    case Condensed = 'condensed';
    case Display = 'display';
    case Oswald = 'oswald';
    case Heavy = 'heavy';
    case Grotesk = 'grotesk';
    case Serif = 'serif';
    case Cinzel = 'cinzel';
    case Blackletter = 'blackletter';
    case Fraktur = 'fraktur';
    case Marker = 'marker';
    case Retro = 'retro';
    case Neon = 'neon';
    case Glitch = 'glitch';
    case Mono = 'mono';

    public function label(): string
    {
        return match ($this) {
            self::Condensed => 'Condensed',
            self::Display => 'Poster',
            self::Oswald => 'Kompakt',
            self::Heavy => 'Fett',
            self::Grotesk => 'Modern',
            self::Serif => 'Elegant',
            self::Cinzel => 'Antik',
            self::Blackletter => 'Metal',
            self::Fraktur => 'Fraktur',
            self::Marker => 'Handschrift',
            self::Retro => 'Retro',
            self::Neon => 'Neonröhre',
            self::Glitch => 'Glitch',
            self::Mono => 'Schreibmaschine',
        };
    }

    public function headingFamily(): string
    {
        return match ($this) {
            self::Condensed => 'Bebas Neue',
            self::Display => 'Anton',
            self::Oswald => 'Oswald',
            self::Heavy => 'Archivo Black',
            self::Grotesk => 'Space Grotesk',
            self::Serif => 'Playfair Display',
            self::Cinzel => 'Cinzel',
            self::Blackletter => 'Pirata One',
            self::Fraktur => 'UnifrakturMaguntia',
            self::Marker => 'Permanent Marker',
            self::Retro => 'Righteous',
            self::Neon => 'Monoton',
            self::Glitch => 'Rubik Glitch',
            self::Mono => 'Space Mono',
        };
    }

    /**
     * Parameter "family=…" für https://fonts.googleapis.com/css2 (ohne &display).
     */
    public function googleFamily(): string
    {
        return 'family='.match ($this) {
            self::Oswald => 'Oswald:wght@700',
            self::Grotesk => 'Space+Grotesk:wght@700',
            self::Serif => 'Playfair+Display:ital,wght@0,700;1,700',
            self::Cinzel => 'Cinzel:wght@700',
            self::Mono => 'Space+Mono:wght@400;700',
            default => str_replace(' ', '+', $this->headingFamily()),
        };
    }

    /**
     * Schriftschnitt, Großschreibung und Laufweite der Überschriften (CSS-Variablen der Website).
     *
     * @return array{weight: int, transform: string, spacing: string}
     */
    public function style(): array
    {
        return [
            'weight' => in_array($this, [self::Oswald, self::Grotesk, self::Serif, self::Cinzel, self::Mono], true) ? 700 : 400,
            'transform' => in_array($this, [self::Condensed, self::Display, self::Oswald], true) ? 'uppercase' : 'none',
            'spacing' => match ($this) {
                self::Condensed, self::Display => '.02em',
                self::Grotesk => '-.03em',
                default => '0',
            },
        ];
    }

    /**
     * Passender Fließtext, solange die Band keinen eigenen wählt.
     */
    public function defaultBodyFont(): WebsiteBodyFontEnum
    {
        return match ($this) {
            self::Serif, self::Cinzel => WebsiteBodyFontEnum::SourceSans,
            self::Fraktur => WebsiteBodyFontEnum::Lora,
            self::Mono => WebsiteBodyFontEnum::SpaceMono,
            default => WebsiteBodyFontEnum::Inter,
        };
    }
}
