<?php

namespace App\Actions\BandManager\Availability;

use App\Enums\BandManager\AvailabilityStatusEnum;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Setzt den Ampel-Status für alle Tage von $from bis $to (beide inklusiv). Liefert die Anzahl der Tage.
 */
class SetAvailabilityAction
{
    public function execute(Band $band, CarbonImmutable $from, CarbonImmutable $to, AvailabilityStatusEnum $status, ?string $note, ?int $userId): int
    {
        $rows = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $rows[] = [
                'band_id' => $band->id,
                'date' => $day->format('Y-m-d'),
                'status' => $status->value,
                'note' => $note,
                'updated_by' => $userId,
            ];
        }

        DB::transaction(fn () => Availability::query()->upsert($rows, ['band_id', 'date'], ['status', 'note', 'updated_by']));

        return count($rows);
    }
}
