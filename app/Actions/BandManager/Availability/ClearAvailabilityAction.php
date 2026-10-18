<?php

namespace App\Actions\BandManager\Availability;

use App\Models\BandManager\Band;
use Carbon\CarbonImmutable;

/**
 * Löscht alle Ampel-Einträge von $from bis $to (beide inklusiv). Liefert die Anzahl gelöschter Tage.
 */
class ClearAvailabilityAction
{
    public function execute(Band $band, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return $band->availabilities()
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->delete();
    }
}
