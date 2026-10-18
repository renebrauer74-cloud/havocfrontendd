<?php

namespace App\Actions\BandManager\Team;

use App\Enums\BandManager\AllergyEnum;
use App\Models\BandManager\TeamMember;

/**
 * Allergien für die Anzeige, mit Komma getrennt („Gluten, Laktose, Sellerie“) – bei „Sonstiges“ der eigene Text,
 * ohne Angabe ein leerer Text.
 */
class DescribeTeamMemberAllergyAction
{
    public function execute(TeamMember $member): string
    {
        return collect($member->allergies)
            ->map(fn (AllergyEnum $allergy): ?string => $allergy === AllergyEnum::Other ? $member->allergy_other : $allergy->label())
            ->filter()
            ->join(', ');
    }
}
