<?php

namespace App\Actions\BandManager\Riders;

use App\Actions\BandManager\Team\DescribeTeamMemberAllergyAction;
use App\Actions\BandManager\Team\DescribeTeamMemberRoleAction;
use App\Models\BandManager\Band;
use App\Models\BandManager\TeamMember;

/**
 * Reisegruppe für den Catering Rider aus „Band/Team“ (Band zuerst, dann Crew) – mit Ernährung und Allergie aus dem Steckbrief.
 */
class SuggestCateringRiderPeopleAction
{
    public function __construct(
        private DescribeTeamMemberRoleAction $describeRole,
        private DescribeTeamMemberAllergyAction $describeAllergy,
    ) {}

    /**
     * @return list<array{name: string, role: string, diet: string, allergy: string}>
     */
    public function execute(Band $band): array
    {
        return $band->teamMembers()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->sortBy(fn (TeamMember $person): int => $person->isCrew() ? 1 : 0)
            ->map(fn (TeamMember $person): array => [
                'name' => trim($person->first_name.' '.$person->last_name).($person->stage_name ? ' („'.$person->stage_name.'“)' : ''),
                'role' => $this->describeRole->execute($person),
                'diet' => (string) $person->diet,
                'allergy' => $this->describeAllergy->execute($person),
            ])
            ->values()
            ->all();
    }
}
