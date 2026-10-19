<?php

namespace App\Enums\BandManager;

/**
 * Agenturvertrag (zwischen Agentur und Band) oder Gastspielvertrag (zu einem Gig).
 */
enum ContractKindEnum: string
{
    case Agency = 'agency';
    case Gig = 'gig';

    public function label(): string
    {
        return match ($this) {
            self::Agency => 'Agenturvertrag',
            self::Gig => 'Gastspielvertrag',
        };
    }
}
