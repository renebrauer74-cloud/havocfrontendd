<?php

namespace App\Enums\BandManager;

/**
 * Ampel-Status einer Band an einem Tag (Wert max. 20 Zeichen, Spalte bm_availabilities.status).
 */
enum AvailabilityStatusEnum: string
{
    case Green = 'green';
    case Orange = 'orange';
    case Red = 'red';

    public function label(): string
    {
        return match ($this) {
            self::Green => 'Verfügbar',
            self::Orange => 'Muss besprochen werden',
            self::Red => 'Termin nicht möglich',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Green => '#22c55e',
            self::Orange => '#f59e0b',
            self::Red => '#ef4444',
        };
    }
}
