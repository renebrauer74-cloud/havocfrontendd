<?php

namespace App\Services\BandManager;

use App\Actions\BandManager\Team\DescribeTeamMemberAllergyAction;
use App\Actions\BandManager\Team\DescribeTeamMemberRoleAction;
use App\Actions\MediaLibrary\CreatePdfImageAction;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Day Sheet eines Gigs als PDF (Vorlage: resources/views/band-manager/gigs/pdf.blade.php).
 * Location Sheets kommen ans Ende: Bilder als eigene Seiten im Day Sheet, PDFs Seite für Seite dahinter.
 */
class DaySheet
{
    public function __construct(
        private Pdf $pdf,
        private DescribeTeamMemberRoleAction $describeRole,
        private DescribeTeamMemberAllergyAction $describeAllergy,
        private CreatePdfImageAction $createPdfImage,
    ) {}

    public function render(Gig $gig): string
    {
        $sheets = $gig->getMedia(MediaCollectionEnum::LocationSheets->value);
        $images = [];
        $pdfs = [];

        foreach ($sheets as $sheet) {
            if (in_array($sheet->mime_type, Gig::LOCATION_SHEET_IMAGE_TYPES, true)) {
                $image = $this->createPdfImage->execute($sheet, MediaConversionEnum::Preview->value);

                if ($image !== null) {
                    $images[] = ['name' => $sheet->file_name, 'image' => $image];
                }
            } else {
                $pdfs[] = (string) stream_get_contents($sheet->stream());
            }
        }

        $html = view('band-manager.gigs.pdf', [
            'gig' => $gig,
            'band' => $gig->band,
            'logo' => $this->pdf->logo(),
            'team' => $this->travelParty($gig),
            'catering' => config('band-manager.gigs.catering_options'),
            'locationSheets' => $sheets->map(fn (Media $sheet): string => $sheet->file_name)->all(),
            'locationSheetImages' => $images,
        ])->render();

        $appendedPages = array_sum(array_map(fn (string $pdf): int => $this->pdf->pageCount($pdf) ?? 0, $pdfs));
        $daySheet = $this->pdf->render(
            $html,
            'HAVOC AGENCY · Day Sheet · Stand '.now()->format('d.m.Y H:i').' · Seite {PAGE_NUM} von {PAGE_COUNT}',
            appendedPages: $appendedPages,
        );

        return $this->pdf->append($daySheet, $pdfs);
    }

    public function fileName(Gig $gig): string
    {
        return 'Havoc_DaySheet_'.$gig->date.'_'.$this->pdf->fileSlug($gig->band->name)
            .($gig->city ? '_'.$this->pdf->fileSlug($gig->city) : '').'.pdf';
    }

    /**
     * Am Gig ausgewählte Reisegruppe aus Band & Team (Band zuerst, dann Crew).
     *
     * @return list<array{crew: bool, name: string, role: string, phone: string, diet: string}>
     */
    public function travelParty(Gig $gig): array
    {
        return $gig->travelers()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->sortBy(fn (TeamMember $person): int => $person->isCrew() ? 1 : 0)
            ->map(fn (TeamMember $person): array => [
                'crew' => $person->isCrew(),
                'name' => trim($person->first_name.' '.$person->last_name).($person->stage_name ? ' („'.$person->stage_name.'“)' : ''),
                'role' => $this->describeRole->execute($person),
                'phone' => (string) $person->phone,
                'diet' => $this->dietWithAllergy($person),
            ])
            ->values()
            ->all();
    }

    /**
     * "vegan · Allergie: Gluten" – leere Angaben fallen weg.
     */
    private function dietWithAllergy(TeamMember $person): string
    {
        $allergy = $this->describeAllergy->execute($person);

        return collect([$person->diet, $allergy !== '' ? 'Allergie: '.$allergy : null])->filter()->join(' · ');
    }
}
