<?php

namespace App\Actions\BandManager\Riders;

use App\Models\BandManager\Band;
use App\Models\BandManager\TeamMember;

/**
 * Besetzung für den Tech Rider aus den Bandmitgliedern unter „Band/Team“ (ohne Crew).
 */
class SuggestTechRiderLineUpAction
{
    /**
     * @return list<array{name: string, instrument: string}>
     */
    public function execute(Band $band): array
    {
        return $band->teamMembers()
            ->where('type', TeamMember::TYPE_MEMBER)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (TeamMember $person): array => [
                'name' => $person->stage_name ?: trim($person->first_name.' '.$person->last_name),
                'instrument' => (string) $person->position,
            ])
            ->values()
            ->all();
    }
}
