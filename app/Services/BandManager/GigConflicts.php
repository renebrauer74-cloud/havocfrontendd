<?php

namespace App\Services\BandManager;

use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use App\Models\BandManager\Gig;
use Carbon\CarbonImmutable;

/**
 * Prüft Gigs gegen den Ampel-Kalender und gegen Doppelbuchungen – in beide Richtungen.
 */
class GigConflicts
{
    /**
     * Spricht an diesem Tag etwas gegen einen Gig? "blocking" muss bestätigt werden, "hints" sind nur Hinweise.
     *
     * @return array{blocking: list<string>, hints: list<string>}
     */
    public function check(int $bandId, string $date, ?int $ignoreGigId = null): array
    {
        $blocking = [];
        $hints = [];
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date)->format('d.m.Y');

        $availability = Availability::query()->where('band_id', $bandId)->where('date', $date)->first();

        if ($availability !== null) {
            $label = $availability->statusDefinition()['label'];
            $note = $availability->note ? ' (Notiz: „'.$availability->note.'“)' : '';

            if ($availability->status === 'red') {
                $blocking[] = "Am {$day} ist die Band im Kalender als „{$label}“ eingetragen{$note}.";
            } elseif ($availability->status === 'orange') {
                $hints[] = "Am {$day} steht im Kalender „{$label}“{$note}. Bitte mit der Band abstimmen.";
            }
        }

        // Doppelbuchung – abgesagte Gigs zählen nicht
        Gig::query()
            ->where('band_id', $bandId)
            ->where('date', $date)
            ->notCancelled()
            ->when($ignoreGigId, fn ($query) => $query->whereKeyNot($ignoreGigId))
            ->pluck('title')
            ->each(function (string $title) use (&$blocking, $day): void {
                $blocking[] = "Am {$day} ist bereits ein Gig eingetragen: „{$title}“.";
            });

        return ['blocking' => $blocking, 'hints' => $hints];
    }

    /**
     * Umgekehrte Prüfung: Die Band setzt Tage auf Rot, an denen schon ein Gig steht.
     *
     * @return list<string>
     */
    public function calendarWarnings(Band $band, CarbonImmutable $from, CarbonImmutable $to, string $status): array
    {
        if ($status !== 'red') {
            return [];
        }

        return $band->gigs()
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->notCancelled()
            ->orderBy('date')
            ->get()
            ->map(fn (Gig $gig): string => 'Am '.CarbonImmutable::createFromFormat('!Y-m-d', $gig->date)->format('d.m.Y')
                .' ist bereits der Gig „'.$gig->title.'“ eingetragen. Bitte die Agentur informieren.')
            ->all();
    }
}
