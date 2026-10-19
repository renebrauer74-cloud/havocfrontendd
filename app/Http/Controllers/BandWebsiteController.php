<?php

namespace App\Http\Controllers;

use App\Actions\BandManager\Website\CollectWebsiteContentAction;
use App\Actions\BandManager\Website\RespondWithWebsiteMediaAction;
use App\Models\BandManager\BandWebsite;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Öffentliche Band-Websites aus dem Website Creator (/sites/{slug}). Unveröffentlichte Websites gibt es nicht (404),
 * Bilder nur, wenn sie auf der Website zu sehen sind.
 */
class BandWebsiteController extends Controller
{
    public function show(BandWebsite $website, CollectWebsiteContentAction $collectContent): View
    {
        abort_unless($website->is_published, 404);

        return view('band-website.show', [
            'website' => $website,
            'band' => $website->band,
            'content' => $collectContent->execute($website),
            'preview' => false,
        ]);
    }

    public function media(Request $request, BandWebsite $website, Media $media, CollectWebsiteContentAction $collectContent, RespondWithWebsiteMediaAction $respondWithMedia): StreamedResponse
    {
        abort_unless($website->is_published && in_array($media->id, $collectContent->execute($website)['media_ids'], true), 404);

        return $respondWithMedia->execute($request, $media, true);
    }
}
