<?php

namespace App\Actions\BandManager\Riders;

use App\Actions\MediaLibrary\CreatePdfImageAction;
use App\Enums\BandManager\CateringMealEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\CateringRider;
use App\Services\BandManager\Pdf;

/**
 * Catering Rider als PDF (Vorlage: resources/views/band-manager/riders/catering/pdf.blade.php) –
 * mit dem Bandlogo aus dem Bandprofil oben in der Mitte. Leere Ernährungs- und Allergie-Felder
 * der Reisegruppe kommen aktuell aus „Band/Team“.
 */
class RenderCateringRiderPdfAction
{
    public function __construct(
        private Pdf $pdf,
        private CreatePdfImageAction $createPdfImage,
        private FillCateringRiderPeopleFromTeamAction $fillPeople,
        private SummarizeCateringDietsAction $summarizeDiets,
    ) {}

    public function execute(CateringRider $rider): string
    {
        $band = $rider->band;
        $people = $this->fillPeople->execute($band, $rider->people ?? []);

        $html = view('band-manager.riders.catering.pdf', [
            'rider' => $rider,
            'band' => $band,
            'bandLogo' => $this->createPdfImage->execute($band->profile->getFirstMedia(MediaCollectionEnum::BandLogos->value), MediaConversionEnum::PdfLogo->value),
            'people' => $people,
            'diets' => $this->summarizeDiets->execute($people),
            'meals' => CateringMealEnum::cases(),
        ])->render();

        return $this->pdf->render($html, 'Catering Rider · '.$band->name.' · Stand '.$rider->updated_at?->format('d.m.Y').' · Seite {PAGE_NUM} von {PAGE_COUNT}');
    }
}
