<?php

namespace App\Actions\BandManager\Team;

use App\Enums\BandManager\AllergyEnum;
use App\Models\BandManager\TeamMember;

/**
 * Allergie für die Anzeige – bei „Sonstiges“ der eigene Text, ohne Angabe ein leerer Text.
 */
class DescribeTeamMemberAllergyAction
{
    public function execute(TeamMember $member): string
    {
        if ($member->allergy === AllergyEnum::Other) {
            return (string) $member->allergy_other;
        }

        return (string) $member->allergy?->label();
    }
}
