<?php

namespace App\Enums\BandManager;

/**
 * Designs des Website Creators. Jedes Theme bringt Farben, eine passende Schrift und einen Hero-Stil mit –
 * die Band kann Akzentfarbe, Schrift und Hero danach frei ändern.
 */
enum WebsiteThemeEnum: string
{
    case Noir = 'noir';
    case Neon = 'neon';
    case Zine = 'zine';
    case Arena = 'arena';
    case Desert = 'desert';
    case Doom = 'doom';
    case Clean = 'clean';

    public function label(): string
    {
        return match ($this) {
            self::Noir => 'Noir',
            self::Neon => 'Neon Nights',
            self::Zine => 'Punk Zine',
            self::Arena => 'Arena',
            self::Desert => 'Desert Rock',
            self::Doom => 'Doom Forest',
            self::Clean => 'Clean',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Noir => 'Schwarz, Rot, kompromisslos – für Rock, Punk und Metal.',
            self::Neon => 'Leuchtende Farbverläufe auf Nachtblau – Synth, Pop, Electro.',
            self::Zine => 'Kopierer-Look auf Papier, schräge Fotos mit Klebeband – DIY & Punk.',
            self::Arena => 'Große Bühne, Spotlights, fette Typo – für die ganz großen Hallen.',
            self::Desert => 'Warme Sonnenuntergangsfarben – Stoner, Indie, Americana.',
            self::Doom => 'Erdig, düster, mystisch – Doom, Folk, Post-Metal.',
            self::Clean => 'Hell, ruhig, elegant – Singer/Songwriter, Jazz, Klassik.',
        };
    }

    /**
     * Farben als CSS-Variablen (accent = Vorschlag, solange die Band keine eigene Akzentfarbe wählt).
     *
     * @return array{bg: string, surface: string, text: string, muted: string, accent: string}
     */
    public function colors(): array
    {
        return match ($this) {
            self::Noir => ['bg' => '#0a0a0b', 'surface' => '#141416', 'text' => '#f2f2f3', 'muted' => '#9a9aa3', 'accent' => '#e5383b'],
            self::Neon => ['bg' => '#0b0717', 'surface' => '#160f2e', 'text' => '#f5f0ff', 'muted' => '#a99cc9', 'accent' => '#ff2bd6'],
            self::Zine => ['bg' => '#efe9dc', 'surface' => '#fffaf0', 'text' => '#141414', 'muted' => '#5d574c', 'accent' => '#ff3b1f'],
            self::Arena => ['bg' => '#060a13', 'surface' => '#0f1626', 'text' => '#eef3ff', 'muted' => '#8d9bb8', 'accent' => '#ffc400'],
            self::Desert => ['bg' => '#1a0f0a', 'surface' => '#26170f', 'text' => '#fbeee0', 'muted' => '#c4a58c', 'accent' => '#ff7a2f'],
            self::Doom => ['bg' => '#0d0f0b', 'surface' => '#171a13', 'text' => '#e9e4d4', 'muted' => '#9a9580', 'accent' => '#b08d57'],
            self::Clean => ['bg' => '#ffffff', 'surface' => '#f4f4f6', 'text' => '#121216', 'muted' => '#6b6b75', 'accent' => '#2563eb'],
        };
    }

    public function isDark(): bool
    {
        return ! in_array($this, [self::Zine, self::Clean], true);
    }

    public function defaultFont(): WebsiteFontEnum
    {
        return match ($this) {
            self::Noir => WebsiteFontEnum::Condensed,
            self::Neon => WebsiteFontEnum::Grotesk,
            self::Zine => WebsiteFontEnum::Marker,
            self::Arena => WebsiteFontEnum::Display,
            self::Desert => WebsiteFontEnum::Serif,
            self::Doom => WebsiteFontEnum::Blackletter,
            self::Clean => WebsiteFontEnum::Serif,
        };
    }

    public function defaultHeroStyle(): WebsiteHeroStyleEnum
    {
        return match ($this) {
            self::Noir, self::Arena, self::Doom => WebsiteHeroStyleEnum::Fullscreen,
            self::Neon => WebsiteHeroStyleEnum::Typo,
            self::Zine, self::Clean => WebsiteHeroStyleEnum::Split,
            self::Desert => WebsiteHeroStyleEnum::Logo,
        };
    }
}
