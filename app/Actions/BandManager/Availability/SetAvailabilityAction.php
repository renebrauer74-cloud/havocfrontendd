<?php

namespace App\Actions\BandManager\Availability;

use App\Enums\BandManager\AvailabilityStatusEnum;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Setzt den Status für alle Tage von $from bis $to (beide inklusiv). Liefert die Anzahl der Tage.
 * Ohne $startTime gilt der Eintrag als ganztägig.
 */
class SetAvailabilityAction
{
    public function execute(
        Band $band,
        CarbonImmutable $from,
        CarbonImmutable $to,
        AvailabilityStatusEnum $status,
        ?string $note,
        ?int $userId,
        ?string $title = null,
        ?string $startTime = null,
        ?string $endTime = null,
    ): int {
        $rows = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $rows[] = [
                'band_id' => $band->id,
                'date' => $day->format('Y-m-d'),
                'status' => $status->value,
                'title' => $title,
                'start_time' => $startTime,
                'end_time' => $startTime !== null ? $endTime : null,
                'note' => $note,
                'updated_by' => $userId,
            ];
        }

        DB::transaction(fn () => Availability::query()->upsert(
            $rows,
            ['band_id', 'date'],
            ['status', 'title', 'start_time', 'end_time', 'note', 'updated_by'],
        ));

        return count($rows);
    }
}
