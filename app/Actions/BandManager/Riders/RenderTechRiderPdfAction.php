<?php

namespace App\Actions\BandManager\Riders;

use App\Actions\MediaLibrary\CreatePdfImageAction;
use App\Enums\BandManager\BacklineProviderEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\TechRider;
use App\Services\BandManager\Pdf;

/**
 * Tech Rider als PDF (Vorlage: resources/views/band-manager/riders/tech/pdf.blade.php).
 */
class RenderTechRiderPdfAction
{
    public function __construct(private Pdf $pdf, private CreatePdfImageAction $createPdfImage) {}

    public function execute(TechRider $rider): string
    {
        $html = view('band-manager.riders.tech.pdf', [
            'rider' => $rider,
            'band' => $rider->band,
            'logo' => $this->pdf->logo(),
            'stagePlot' => $this->createPdfImage->execute($rider->getFirstMedia(MediaCollectionEnum::StagePlots->value)),
            'providers' => BacklineProviderEnum::cases(),
        ])->render();

        return $this->pdf->render($html, 'Technical Rider · '.$rider->band->name.' · Stand '.$rider->updated_at?->format('d.m.Y').' · Seite {PAGE_NUM} von {PAGE_COUNT}');
    }
}
