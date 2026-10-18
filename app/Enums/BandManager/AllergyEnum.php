<?php

namespace App\Enums\BandManager;

/**
 * Allergie einer Person unter „Band/Team“ – die drei häufigsten plus „Sonstiges“ mit eigenem Text.
 */
enum AllergyEnum: string
{
    case Nuts = 'nuts';
    case Gluten = 'gluten';
    case Lactose = 'lactose';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Nuts => 'Nüsse / Erdnüsse',
            self::Gluten => 'Gluten',
            self::Lactose => 'Laktose',
            self::Other => 'Sonstiges',
        };
    }
}
