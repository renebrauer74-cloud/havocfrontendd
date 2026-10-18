<?php

namespace App\Actions\BandManager\Riders;

use App\Actions\MediaLibrary\CreatePdfImageAction;
use App\Enums\BandManager\StagePlotElementEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\StagePlot;
use App\Services\BandManager\Pdf;

/**
 * Stage Plot als PDF im Querformat (Vorlage: resources/views/band-manager/riders/stage-plot/pdf.blade.php) –
 * Bandlogo, das gezeichnete Bild und darunter die beschrifteten Elemente als Legende.
 */
class RenderStagePlotPdfAction
{
    public function __construct(private Pdf $pdf, private CreatePdfImageAction $createPdfImage) {}

    public function execute(StagePlot $plot): string
    {
        $band = $plot->band;

        $legend = collect($plot->items ?? [])
            ->filter(fn (array $item): bool => $item['label'] !== '' && $item['type'] !== StagePlotElementEnum::Text->value)
            ->map(fn (array $item): array => ['element' => StagePlotElementEnum::from($item['type'])->label(), 'label' => $item['label']])
            ->values()
            ->all();

        $html = view('band-manager.riders.stage-plot.pdf', [
            'plot' => $plot,
            'band' => $band,
            'bandLogo' => $this->createPdfImage->execute($band->profile->getFirstMedia(MediaCollectionEnum::BandLogos->value), MediaConversionEnum::PdfLogo->value),
            'image' => $this->createPdfImage->execute($plot->getFirstMedia(MediaCollectionEnum::StagePlotImages->value)),
            'legend' => $legend,
        ])->render();

        return $this->pdf->render($html, 'Stage Plot · '.$band->name.' · Stand '.$plot->updated_at?->format('d.m.Y').' · Seite {PAGE_NUM} von {PAGE_COUNT}', 'landscape');
    }
}
