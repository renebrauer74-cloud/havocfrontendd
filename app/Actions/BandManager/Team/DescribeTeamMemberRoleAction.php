<?php

namespace App\Actions\BandManager\Team;

use App\Enums\BandManager\CrewRoleEnum;
use App\Models\BandManager\TeamMember;

/**
 * Rolle für die Anzeige. Crew: Aufgabe (bei „Sonstiges“ der eigene Text) plus Zusatz – Band: Instrument / Rolle.
 */
class DescribeTeamMemberRoleAction
{
    public function execute(TeamMember $member): string
    {
        if (! $member->isCrew()) {
            return $member->position ?: 'Bandmitglied';
        }

        $role = $member->crew_role === CrewRoleEnum::Other && $member->crew_role_other
            ? $member->crew_role_other
            : ($member->crew_role?->label() ?? 'Team');

        return $member->position ? $role.' · '.$member->position : $role;
    }
}
