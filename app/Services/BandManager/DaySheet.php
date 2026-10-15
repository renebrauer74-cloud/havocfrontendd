<?php

namespace App\Services\BandManager;

use App\Models\BandManager\Gig;

/**
 * Day Sheet eines Gigs als PDF (Vorlage: resources/views/band-manager/gigs/pdf.blade.php).
 */
class DaySheet
{
    public function __construct(private Pdf $pdf) {}

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
    private function travelParty(Gig $gig): array
    {
        return [];
    }
}
