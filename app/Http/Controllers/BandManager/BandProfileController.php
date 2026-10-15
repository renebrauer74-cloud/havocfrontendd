<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\BandProfileRequest;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\BandProfile;
use App\Services\BandManager\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
            'profile' => $band->profileOrNew(),
            'platforms' => BandProfile::platforms(),
            'bioMax' => (int) config('band-manager.team.bio_max'),
            'logoMaxMb' => (int) config('band-manager.team.max_logo_mb'),
        ]);
    }

    public function update(BandProfileRequest $request, Band $band): RedirectResponse
    {
        $profile = $band->profileOrNew();
        $oldLogo = $profile->logoPath();

        $profile->fill([
            'bio_de' => $request->validated('bio_de'),
            'bio_en' => $request->validated('bio_en'),
            'links' => $request->links() ?: null,
            'updated_by' => $request->user()->id,
        ]);

        // Logo-Datei zuerst speichern – schlägt das fehl, bleibt alles unverändert
        if ($request->hasFile('logo')) {
            $profile->logo = BandProfile::storeLogo($request->file('logo'), $band->id);
        } elseif ($request->boolean('remove_logo')) {
            $profile->logo = null;
        }

        $profile->save();

        // Altes Logo erst jetzt löschen
        if ($oldLogo !== null && $oldLogo !== $profile->logoPath()) {
            Storage::disk('band-manager')->delete($oldLogo);
        }

        ActivityLog::record('team.profile_saved', ['band_id' => $band->id]);

        return to_route('band-manager.team.band', $band)->with('success', 'Bandprofil wurde gespeichert.');
    }

    /**
     * Bandlogo ausliefern – mit ?download=1 als Download.
     */
    public function logo(Request $request, Band $band, Pdf $pdf): StreamedResponse
    {
        Gate::authorize('access', $band);

        $path = $band->profile?->logoPath();
        $disk = Storage::disk('band-manager');
        abort_if($path === null || ! $disk->exists($path), 404);

        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=86400'];

        if ($request->boolean('download')) {
            $name = 'Logo_'.($pdf->fileSlug($band->name) ?: 'Band').'.'.pathinfo($path, PATHINFO_EXTENSION);

            return $disk->download($path, $name, $headers);
        }

        return $disk->response($path, null, $headers);
    }
}
