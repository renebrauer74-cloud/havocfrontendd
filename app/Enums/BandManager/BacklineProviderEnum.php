<?php

namespace App\Enums\BandManager;

/**
 * Wer ein Backline-Teil stellt (Tech Rider).
 */
enum BacklineProviderEnum: string
{
    case Band = 'band';
    case Venue = 'venue';

    public function label(): string
    {
        return match ($this) {
            self::Band => 'Bringt die Band mit',
            self::Venue => 'Stellt der Veranstalter',
        };
    }
}
