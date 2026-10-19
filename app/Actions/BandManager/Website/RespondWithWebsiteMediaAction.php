<?php

namespace App\Actions\BandManager\Website;

use App\Enums\MediaConversionEnum;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bild für die Band-Website: die verkleinerte Vorschau, wenn es eine gibt (Fotos), sonst das Original (Logo).
 */
class RespondWithWebsiteMediaAction
{
    public function execute(Request $request, Media $media, bool $isPublic): StreamedResponse
    {
        $response = $media->hasGeneratedConversion(MediaConversionEnum::Preview->value)
            ? $media->toInlineResponse($request, MediaConversionEnum::Preview->value)
            : $media->toInlineResponse($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', $isPublic ? 'public, max-age=3600' : 'private, no-store');

        return $response;
    }
}
