<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Models\BandManager\Gig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Location Sheets eines Gigs ansehen (alle mit Zugriff auf das Day Sheet) und löschen (wer den Gig bearbeiten darf).
 * Hochgeladen werden sie über das Gig-Formular (AddLocationSheetsAction).
 */
class GigLocationSheetController extends Controller
{
    /**
     * Im Browser öffnen – PDF, JPG und PNG sind sichere Formate (siehe Gig::LOCATION_SHEET_TYPES).
     */
    public function show(Request $request, Gig $gig, Media $media): StreamedResponse
    {
        Gate::authorize('view', $gig);
        abort_unless($media->collection_name === MediaCollectionEnum::LocationSheets->value, 404);

        return $media->toInlineResponse($request);
    }

    public function destroy(Gig $gig, Media $media): RedirectResponse
    {
        Gate::authorize('update', $gig);
        abort_unless($media->collection_name === MediaCollectionEnum::LocationSheets->value, 404);

        $media->delete();

        app(RecordActivityAction::class)->execute('gig.location_sheet_deleted', ['gig_id' => $gig->id, 'band_id' => $gig->band_id, 'media_id' => $media->id]);

        return to_route('band-manager.gigs.edit', $gig)->with('success', 'Location Sheet wurde gelöscht.');
    }
}
