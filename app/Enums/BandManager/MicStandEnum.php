<?php

namespace App\Enums\BandManager;

/**
 * Stativ für einen Kanal der Kanalliste (Tech Rider).
 */
enum MicStandEnum: string
{
    case TallBoom = 'tall_boom';
    case ShortBoom = 'short_boom';
    case Straight = 'straight';
    case Clamp = 'clamp';
    case Own = 'own';

    public function label(): string
    {
        return match ($this) {
            self::TallBoom => 'Galgen groß',
            self::ShortBoom => 'Galgen klein',
            self::Straight => 'Gerade',
            self::Clamp => 'Klemme',
            self::Own => 'Eigenes (Band)',
        };
    }
}
