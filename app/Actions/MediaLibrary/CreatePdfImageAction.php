<?php

namespace App\Actions\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Bild aus der Media Library als data-URI für Dompdf, mit Maßen fürs Layout.
 * Dompdf kann nur JPEG und PNG; sehr große Dateien bleiben draußen, damit das PDF klein bleibt.
 */
class CreatePdfImageAction
{
    private const int MAX_BYTES = 6 * 1048576;

    /**
     * Die Conversion wird genutzt, wenn es sie gibt – sonst das Original.
     *
     * @return array{src: string, w: int, h: int}|null
     */
    public function execute(?Media $media, string $conversion = ''): ?array
    {
        if ($media === null) {
            return null;
        }

        if ($conversion !== '' && ! $media->hasGeneratedConversion($conversion)) {
            $conversion = '';
        }

        if ($conversion === '' && $media->size > self::MAX_BYTES) {
            return null;
        }

        $contents = (string) stream_get_contents($media->stream($conversion));
        $imageSize = @getimagesizefromstring($contents);

        if ($imageSize === false || ! in_array($imageSize['mime'], ['image/jpeg', 'image/png'], true) || strlen($contents) > self::MAX_BYTES) {
            return null;
        }

        return [
            'src' => 'data:'.$imageSize['mime'].';base64,'.base64_encode($contents),
            'w' => $imageSize[0],
            'h' => $imageSize[1],
        ];
    }
}
