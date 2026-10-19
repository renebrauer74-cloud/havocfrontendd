<?php

namespace App\Enums\BandManager;

/**
 * Gestaltung des Kopfbereichs (Hero) der Band-Website.
 */
enum WebsiteHeroStyleEnum: string
{
    case Fullscreen = 'fullscreen';
    case Split = 'split';
    case Logo = 'logo';
    case Typo = 'typo';

    public function label(): string
    {
        return match ($this) {
            self::Fullscreen => 'Vollbild-Foto',
            self::Split => 'Foto + Text',
            self::Logo => 'Großes Logo',
            self::Typo => 'Nur Schrift',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Fullscreen => 'Das Titelfoto füllt den ganzen Bildschirm, der Bandname liegt darüber.',
            self::Split => 'Links Bandname und Slogan, rechts das Titelfoto.',
            self::Logo => 'Euer Bandlogo groß in der Mitte, das Foto dahinter.',
            self::Typo => 'Riesiger Bandname mit animiertem Farbverlauf – ganz ohne Foto.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Fullscreen => 'aspect-ratio',
            self::Split => 'layout-split',
            self::Logo => 'badge-ad',
            self::Typo => 'fonts',
        };
    }
}
