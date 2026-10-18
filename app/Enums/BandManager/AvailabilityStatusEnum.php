<?php

namespace App\Enums\BandManager;

/**
 * Status einer Band an einem Tag (Wert max. 20 Zeichen, Spalte bm_availabilities.status):
 * Ampel (Grün/Orange/Rot) plus eigene Termine der Band (Probe, Sonstiges mit eigenem Titel).
 */
enum AvailabilityStatusEnum: string
{
    case Green = 'green';
    case Orange = 'orange';
    case Red = 'red';
    case Blue = 'blue';
    case Pink = 'pink';

    public function label(): string
    {
        return match ($this) {
            self::Green => 'Verfügbar',
            self::Orange => 'Muss besprochen werden',
            self::Red => 'Termin nicht möglich',
            self::Blue => 'Probe',
            self::Pink => 'Sonstiges',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Green => '#22c55e',
            self::Orange => '#f59e0b',
            self::Red => '#ef4444',
            self::Blue => '#3b82f6',
            self::Pink => '#ec4899',
        };
    }

    /**
     * Bei „Sonstiges“ legt die Band selbst fest, was ansteht (Musikvideo, Fotoshooting …).
     */
    public function requiresTitle(): bool
    {
        return $this === self::Pink;
    }
}
