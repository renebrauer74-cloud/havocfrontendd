<?php

namespace App\Actions\BandManager\Availability;

use App\Models\BandManager\Availability;

/**
 * Uhrzeit eines Kalendereintrags zum Anzeigen: „14:00–18:00“, „ab 14:00“ oder null (ganztägig).
 */
class DescribeAvailabilityTimeAction
{
    public function execute(Availability $availability): ?string
    {
        if ($availability->isAllDay()) {
            return null;
        }

        $start = substr((string) $availability->start_time, 0, 5);

        return $availability->end_time !== null
            ? $start.'–'.substr((string) $availability->end_time, 0, 5)
            : 'ab '.$start;
    }
}
