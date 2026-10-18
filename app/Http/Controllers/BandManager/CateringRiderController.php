<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Riders\FillCateringRiderPeopleFromTeamAction;
use App\Actions\BandManager\Riders\RenderCateringRiderPdfAction;
use App\Actions\BandManager\Riders\SaveCateringRiderAction;
use App\Actions\BandManager\Riders\SuggestCateringRiderPeopleAction;
use App\Enums\BandManager\AllergyEnum;
use App\Enums\BandManager\CateringBuyoutEnum;
use App\Enums\BandManager\CateringMealEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\CateringRiderRequest;
use App\Models\BandManager\Band;
use App\Services\BandManager\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Rider Creator → Catering Rider: Reisegruppe mit Ernährung, Mahlzeiten, Buyout, Getränke, Garderobe
 * und eigene Abschnitte – als Formular zum Ausfüllen und als PDF für Veranstalter.
 */
class CateringRiderController extends Controller
{
    /**
     * Band-Auswahl – Bands mit genau einer Band landen direkt in deren Rider.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)->with('cateringRider')->orderBy('name')->get();

        if (! $user->isAdmin() && $bands->count() === 1) {
            return to_route('band-manager.catering-riders.band', $bands->first());
        }

        return view('band-manager.riders.catering.index', ['bands' => $bands]);
    }

    /**
     * Neue Rider starten mit der Reisegruppe aus „Band/Team“, gespeicherte bekommen leere Ernährungs- und Allergie-Felder von dort ergänzt.
     */
    public function band(Request $request, Band $band, SuggestCateringRiderPeopleAction $suggestPeople, FillCateringRiderPeopleFromTeamAction $fillPeople): View
    {
        Gate::authorize('access', $band);

        $rider = $band->cateringRider;
        $suggestedPeople = $suggestPeople->execute($band);

        return view('band-manager.riders.catering.form', [
            'band' => $band,
            'rider' => $rider,
            'bands' => Band::query()->accessibleBy($request->user())->orderBy('name')->get(),
            'canEdit' => $request->user()->can('riders.edit'),
            'people' => $rider->exists ? $fillPeople->execute($band, $rider->people ?? []) : $suggestedPeople,
            'suggestedPeople' => $suggestedPeople,
            'allergies' => array_filter(AllergyEnum::cases(), fn (AllergyEnum $allergy): bool => $allergy !== AllergyEnum::Other),
            'meals' => CateringMealEnum::cases(),
            'buyoutOptions' => CateringBuyoutEnum::cases(),
            'templates' => config('band-manager.catering_rider.templates'),
            'textMax' => (int) config('band-manager.catering_rider.text_max'),
            'maxBuyout' => (int) config('band-manager.catering_rider.max_buyout'),
        ]);
    }

    public function update(CateringRiderRequest $request, Band $band, SaveCateringRiderAction $saveCateringRider): RedirectResponse
    {
        $saveCateringRider->execute($band, $request->validated(), $request->user());

        app(RecordActivityAction::class)->execute('catering_rider.saved', ['band_id' => $band->id]);

        return to_route('band-manager.catering-riders.band', $band)->with('success', 'Catering Rider wurde gespeichert.');
    }

    /**
     * Catering Rider als PDF – erst nach dem ersten Speichern.
     */
    public function pdf(Band $band, RenderCateringRiderPdfAction $renderPdf, Pdf $pdf): Response|RedirectResponse
    {
        Gate::authorize('access', $band);

        $rider = $band->cateringRider;

        if (! $rider->exists) {
            return to_route('band-manager.catering-riders.band', $band)->with('warning', 'Bitte den Catering Rider zuerst ausfüllen und speichern.');
        }

        $content = $renderPdf->execute($rider);

        app(RecordActivityAction::class)->execute('catering_rider.pdf', ['band_id' => $band->id]);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="CateringRider_'.($pdf->fileSlug($band->name) ?: 'Band').'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
