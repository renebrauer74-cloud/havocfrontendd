<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Riders\RenderTechRiderPdfAction;
use App\Actions\BandManager\Riders\SaveTechRiderAction;
use App\Actions\BandManager\Riders\SuggestTechRiderLineUpAction;
use App\Enums\BandManager\BacklineProviderEnum;
use App\Enums\BandManager\MicStandEnum;
use App\Enums\BandManager\MonitorTypeEnum;
use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\TechRiderRequest;
use App\Models\BandManager\Band;
use App\Services\BandManager\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rider Creator → Tech Rider: Besetzung, Kanalliste, Monitoring, Backline, Bühne, PA, Licht, Strom
 * und eigene Abschnitte – als Formular zum Ausfüllen und als PDF für Veranstalter.
 */
class TechRiderController extends Controller
{
    /**
     * Band-Auswahl – Bands mit genau einer Band landen direkt in deren Rider.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)->with('techRider')->orderBy('name')->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.tech-riders.band', $bands->first());
        }

        return view('band-manager.riders.tech.index', ['bands' => $bands]);
    }

    public function band(Request $request, Band $band, SuggestTechRiderLineUpAction $suggestLineUp): View
    {
        Gate::authorize('access', $band);

        $rider = $band->techRider;

        return view('band-manager.riders.tech.form', [
            'band' => $band,
            'rider' => $rider,
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'canEdit' => $request->user()->can('riders.edit'),
            'suggestedLineUp' => $suggestLineUp->execute($band),
            'hasStagePlot' => $rider->exists && $rider->hasMedia(MediaCollectionEnum::StagePlots->value),
            'stands' => MicStandEnum::cases(),
            'monitorTypes' => MonitorTypeEnum::cases(),
            'providers' => BacklineProviderEnum::cases(),
            'templates' => config('band-manager.tech_rider.templates'),
            'textMax' => (int) config('band-manager.tech_rider.text_max'),
            'stagePlotMaxMb' => (int) config('band-manager.tech_rider.max_stage_plot_mb'),
        ]);
    }

    public function update(TechRiderRequest $request, Band $band, SaveTechRiderAction $saveTechRider): RedirectResponse
    {
        $saveTechRider->execute($band, $request->validated(), $request->user(), $request->file('stage_plot'), $request->boolean('remove_stage_plot'));

        app(RecordActivityAction::class)->execute('tech_rider.saved', ['band_id' => $band->id]);

        return to_route('band-manager.tech-riders.band', $band)->with('success', 'Tech Rider wurde gespeichert.');
    }

    /**
     * Tech Rider als PDF – erst nach dem ersten Speichern.
     */
    public function pdf(Band $band, RenderTechRiderPdfAction $renderPdf, Pdf $pdf): Response|RedirectResponse
    {
        Gate::authorize('access', $band);

        $rider = $band->techRider;

        if (! $rider->exists) {
            return to_route('band-manager.tech-riders.band', $band)->with('warning', 'Bitte den Tech Rider zuerst ausfüllen und speichern.');
        }

        $content = $renderPdf->execute($rider);

        app(RecordActivityAction::class)->execute('tech_rider.pdf', ['band_id' => $band->id]);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="TechRider_'.($pdf->fileSlug($band->name) ?: 'Band').'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Bühnenplan ausliefern – mit ?download=1 als Download.
     */
    public function stagePlot(Request $request, Band $band): StreamedResponse
    {
        Gate::authorize('access', $band);

        $media = $band->techRider->getFirstMedia(MediaCollectionEnum::StagePlots->value);
        abort_if($media === null, 404);

        return $request->boolean('download') ? $media->toResponse($request) : $media->toInlineResponse($request);
    }
}
