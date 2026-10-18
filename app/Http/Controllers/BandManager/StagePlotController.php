<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Riders\RenderStagePlotPdfAction;
use App\Actions\BandManager\Riders\SaveStagePlotAction;
use App\Enums\BandManager\StagePlotElementEnum;
use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\StagePlotRequest;
use App\Models\BandManager\Band;
use App\Services\BandManager\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rider Creator → Stage Plot Designer: Bühne in Draufsicht mit Symbolen zum Hinziehen, Drehen und Beschriften –
 * als PDF (Querformat) und PNG für Veranstalter.
 */
class StagePlotController extends Controller
{
    /**
     * Band-Auswahl – Bands mit genau einer Band landen direkt in deren Stage Plot.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)->with('stagePlot')->orderBy('name')->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.stage-plots.band', $bands->first());
        }

        return view('band-manager.riders.stage-plot.index', ['bands' => $bands]);
    }

    public function band(Request $request, Band $band): View
    {
        Gate::authorize('access', $band);

        $plot = $band->stagePlot;

        return view('band-manager.riders.stage-plot.form', [
            'band' => $band,
            'plot' => $plot,
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'canEdit' => $request->user()->can('riders.edit'),
            'hasImage' => $plot->exists && $plot->hasMedia(MediaCollectionEnum::StagePlotImages->value),
            'elements' => StagePlotElementEnum::cases(),
            'templates' => config('band-manager.stage_plot.templates'),
            'minStage' => (int) config('band-manager.stage_plot.min_stage_m'),
            'maxStage' => (int) config('band-manager.stage_plot.max_stage_m'),
            'maxItems' => (int) config('band-manager.stage_plot.max_items'),
            'textMax' => (int) config('band-manager.stage_plot.text_max'),
        ]);
    }

    public function update(StagePlotRequest $request, Band $band, SaveStagePlotAction $saveStagePlot): RedirectResponse
    {
        $saveStagePlot->execute($band, $request->validated(), $request->user());

        app(RecordActivityAction::class)->execute('stage_plot.saved', ['band_id' => $band->id]);

        return to_route('band-manager.stage-plots.band', $band)->with('success', 'Stage Plot wurde gespeichert.');
    }

    /**
     * Stage Plot als PDF – erst, wenn ein Bild gespeichert ist.
     */
    public function pdf(Band $band, RenderStagePlotPdfAction $renderPdf, Pdf $pdf): Response|RedirectResponse
    {
        Gate::authorize('access', $band);

        $plot = $band->stagePlot;

        if (! $plot->exists || ! $plot->hasMedia(MediaCollectionEnum::StagePlotImages->value)) {
            return to_route('band-manager.stage-plots.band', $band)->with('warning', 'Bitte den Stage Plot zuerst gestalten und speichern.');
        }

        $content = $renderPdf->execute($plot);

        app(RecordActivityAction::class)->execute('stage_plot.pdf', ['band_id' => $band->id]);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="StagePlot_'.($pdf->fileSlug($band->name) ?: 'Band').'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Gezeichnetes Bild (PNG) ausliefern – mit ?download=1 als Download.
     */
    public function image(Request $request, Band $band): StreamedResponse
    {
        Gate::authorize('access', $band);

        $media = $band->stagePlot->getFirstMedia(MediaCollectionEnum::StagePlotImages->value);
        abort_if($media === null, 404);

        return $request->boolean('download') ? $media->toResponse($request) : $media->toInlineResponse($request);
    }
}
