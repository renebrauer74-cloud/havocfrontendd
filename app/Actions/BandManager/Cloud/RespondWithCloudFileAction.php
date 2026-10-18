<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFile;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liefert eine Cloud-Datei aus: sichere Formate (CloudFile::INLINE_TYPES) im Browser, alles andere und
 * auf Wunsch als Download – damit hochgeladenes HTML/SVG kein Skript auf unserer Domain ausführt.
 */
class RespondWithCloudFileAction
{
    public function execute(Request $request, Media $media, bool $download): StreamedResponse
    {
        $response = ! $download && in_array($media->mime_type, CloudFile::INLINE_TYPES, true)
            ? $media->toInlineResponse($request)
            : $media->toResponse($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
