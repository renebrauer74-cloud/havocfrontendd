<?php

namespace App\Actions\BandManager\Availability;

use App\Models\BandManager\Availability;
use Carbon\CarbonImmutable;

/**
 * Anzahl Tage pro Band und Status ab heute: [band_id => [status => anzahl]].
 */
class SummarizeAvailabilityAction
{
    /**
     * @return array<int, array<string, int>>
     */
    public function execute(int $days): array
    {
        $today = CarbonImmutable::today();
        $summary = [];

        Availability::query()
            ->toBase()
            ->selectRaw('band_id, status, COUNT(*) AS days')
            ->whereBetween('date', [$today->format('Y-m-d'), $today->addDays($days)->format('Y-m-d')])
            ->groupBy('band_id', 'status')
            ->get()
            ->each(function (object $row) use (&$summary): void {
                $summary[(int) $row->band_id][(string) $row->status] = (int) $row->days;
            });

        return $summary;
    }
}
