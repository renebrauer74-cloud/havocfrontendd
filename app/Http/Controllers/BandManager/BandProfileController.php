<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Enums\BandManager\PlatformEnum;
use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\BandProfileRequest;
use App\Models\BandManager\Band;
use App\Services\BandManager\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bandprofil: Logo, Biografie (DE/EN), Social Media & Streaming – erscheint auch im Pressekit.
 */
class BandProfileController extends Controller
{
    public function edit(Band $band): View
    {
        Gate::authorize('access', $band);

        return view('band-manager.team.profile', [
            'band' => $band,
            'profile' => $band->profile,
            'platforms' => PlatformEnum::cases(),
            'bioMax' => (int) config('band-manager.team.bio_max'),
            'logoMaxMb' => (int) config('band-manager.team.max_logo_mb'),
        ]);
    }

    public function update(BandProfileRequest $request, Band $band, Pdf $pdf): RedirectResponse
    {
        $profile = $band->profile;

        $profile->fill([
            'bio_de' => $request->validated('bio_de'),
            'bio_en' => $request->validated('bio_en'),
            'links' => $request->links() ?: null,
            'updated_by' => $request->user()->id,
        ]);

        $profile->save();

        // Ein neues Logo ersetzt das alte erst, wenn es sicher gespeichert ist (singleFile)
        if ($request->hasFile('logo')) {
            $profile->addMediaFromRequest('logo')
                ->usingFileName('Logo_'.($pdf->fileSlug($band->name) ?: 'Band').'.'.$request->file('logo')->extension())
                ->toMediaCollection(MediaCollectionEnum::BandLogos->value);
        } elseif ($request->boolean('remove_logo')) {
            $profile->clearMediaCollection(MediaCollectionEnum::BandLogos->value);
        }

        app(RecordActivityAction::class)->execute('team.profile_saved', ['band_id' => $band->id]);

        return to_route('band-manager.team.band', $band)->with('success', 'Bandprofil wurde gespeichert.');
    }

    /**
     * Bandlogo ausliefern – mit ?download=1 als Download.
     */
    public function logo(Request $request, Band $band): StreamedResponse
    {
        Gate::authorize('access', $band);

        $media = $band->profile?->getFirstMedia(MediaCollectionEnum::BandLogos->value);
        abort_if($media === null, 404);

        return $request->boolean('download') ? $media->toResponse($request) : $media->toInlineResponse($request);
    }
}
