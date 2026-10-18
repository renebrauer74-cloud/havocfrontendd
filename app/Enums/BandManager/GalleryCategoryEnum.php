<?php

namespace App\Enums\BandManager;

/**
 * Art eines Galeriebilds. Das Hauptbild ist immer ein Bandfoto.
 */
enum GalleryCategoryEnum: string
{
    case Band = 'band';
    case Live = 'live';
    case Video = 'video';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Band => 'Bandfoto',
            self::Live => 'Live / Gig',
            self::Video => 'Musikvideo (Standbild)',
            self::Other => 'Sonstiges',
        };
    }
}
