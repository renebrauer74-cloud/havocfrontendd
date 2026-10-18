<?php

namespace App\Actions\BandManager\Team;

use App\Models\BandManager\TeamMember;
use Carbon\CarbonImmutable;

/**
 * Stammdaten einer Person für die Profilseite – nur ausgefüllte Felder.
 */
class ListTeamMemberFactsAction
{
    /**
     * @return list<array{0: string, 1: string, 2: ?string}> [Label, Wert, Link]
     */
    public function execute(TeamMember $member): array
    {
        $address = collect([$member->street, trim($member->zip.' '.$member->city), $member->country])->filter()->join("\n");
        $birth = null;

        if ($member->birth_date) {
            $birthDate = CarbonImmutable::parse($member->birth_date);
            $birth = $birthDate->format('d.m.Y').' ('.(int) $birthDate->diffInYears(CarbonImmutable::today()).' Jahre)';
        }

        $rows = [
            ['Geburtsdatum', $birth, null],
            ['Nationalität', $member->nationality, null],
            ['E-Mail', $member->email, $member->email ? 'mailto:'.$member->email : null],
            ['Telefon', $member->phone, $member->phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $member->phone) : null],
            ['Adresse', $address, null],
            ['T-Shirt-Größe', $member->shirt_size, null],
            ['Ernährung / Allergien', $member->diet, null],
            ['Führerschein', $member->driver_license, null],
            ['Notfallkontakt', $member->emergency_contact, null],
        ];

        return array_values(array_filter($rows, fn (array $row): bool => filled($row[1])));
    }
}
