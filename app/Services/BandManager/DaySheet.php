<?php

namespace App\Services\BandManager;

use App\Actions\BandManager\Team\DescribeTeamMemberRoleAction;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;

/**
 * Day Sheet eines Gigs als PDF (Vorlage: resources/views/band-manager/gigs/pdf.blade.php).
 */
class DaySheet
{
    public function __construct(private Pdf $pdf, private DescribeTeamMemberRoleAction $describeRole) {}

    public function render(Gig $gig): string
    {
        $html = view('band-manager.gigs.pdf', [
            'gig' => $gig,
            'band' => $gig->band,
            'logo' => $this->pdf->logo(),
            'team' => $this->travelParty($gig),
            'catering' => config('band-manager.gigs.catering_options'),
        ])->render();

        return $this->pdf->render($html, 'HAVOC AGENCY · Day Sheet · Stand '.now()->format('d.m.Y H:i').' · Seite {PAGE_NUM} von {PAGE_COUNT}');
    }

    public function fileName(Gig $gig): string
    {
        return 'Havoc_DaySheet_'.$gig->date.'_'.$this->pdf->fileSlug($gig->band->name)
            .($gig->city ? '_'.$this->pdf->fileSlug($gig->city) : '').'.pdf';
    }

    /**
     * Reisegruppe aus Band & Team (Band zuerst, dann Crew).
     *
     * @return list<array{crew: bool, name: string, role: string, phone: string, diet: string}>
     */
    public function travelParty(Gig $gig): array
    {
        return $gig->band->teamMembers()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->sortBy(fn (TeamMember $person): int => $person->isCrew() ? 1 : 0)
            ->map(fn (TeamMember $person): array => [
                'crew' => $person->isCrew(),
                'name' => trim($person->first_name.' '.$person->last_name).($person->stage_name ? ' („'.$person->stage_name.'“)' : ''),
                'role' => $this->describeRole->execute($person),
                'phone' => (string) $person->phone,
                'diet' => (string) $person->diet,
            ])
            ->values()
            ->all();
    }
}
