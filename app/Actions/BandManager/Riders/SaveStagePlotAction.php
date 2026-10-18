<?php

namespace App\Actions\BandManager\Riders;

use App\Enums\BandManager\StagePlotElementEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\StagePlot;
use App\Models\BandManager\User;
use App\Services\BandManager\Pdf;

/**
 * Speichert den Stage Plot einer Band: Bühnengröße, Elemente (gerundet, Drehung auf -180…180°, leere Textfelder fallen weg)
 * und das im Browser gezeichnete PNG – ein neues Bild ersetzt das alte erst, wenn es sicher gespeichert ist (singleFile).
 */
class SaveStagePlotAction
{
    public function __construct(private Pdf $pdf) {}

    /**
     * @param  array<string, mixed>  $data  validierte Eingaben aus StagePlotRequest
     */
    public function execute(Band $band, array $data, User $user): StagePlot
    {
        $plot = $band->stagePlot;

        $plot->fill([
            'stage_width' => $data['stage_width'],
            'stage_depth' => $data['stage_depth'],
            'items' => $this->items((array) ($data['items'] ?? [])),
            'notes' => $data['notes'] ?? null,
            'updated_by' => $user->id,
        ]);

        $plot->save();

        if (filled($data['image'] ?? null)) {
            $plot->addMediaFromBase64($data['image'], 'image/png')
                ->usingFileName('StagePlot_'.($this->pdf->fileSlug($band->name) ?: 'Band').'.png')
                ->toMediaCollection(MediaCollectionEnum::StagePlotImages->value);
        }

        return $plot;
    }

    /**
     * @param  array<mixed>  $items
     * @return list<array{type: string, x: int, y: int, w: int, h: int, rotation: int, label: string}>
     */
    private function items(array $items): array
    {
        $clean = [];

        foreach ($items as $item) {
            $item = (array) $item;
            $label = trim((string) ($item['label'] ?? ''));

            if ($item['type'] === StagePlotElementEnum::Text->value && $label === '') {
                continue;
            }

            $rotation = (int) round((float) $item['rotation']) % 360;

            $clean[] = [
                'type' => (string) $item['type'],
                'x' => (int) round((float) $item['x']),
                'y' => (int) round((float) $item['y']),
                'w' => (int) round((float) $item['w']),
                'h' => max(1, (int) round((float) $item['h'])),
                'rotation' => $rotation > 180 ? $rotation - 360 : ($rotation <= -180 ? $rotation + 360 : $rotation),
                'label' => $label,
            ];
        }

        return $clean;
    }
}
