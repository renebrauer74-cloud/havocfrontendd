<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\BlockCalendarDayForGigAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Enums\BandManager\GigStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\GigRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;
use App\Services\BandManager\DaySheet;
use App\Services\BandManager\GigConflicts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GigController extends Controller
{
    public function __construct(private GigConflicts $conflicts) {}

    /**
     * ?band=ID filtert nach Band, ?show=upcoming|past|all (Standard: kommende).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $band = $this->bandFilter($request);

        $show = $request->boolean('past') ? 'past' : $request->query('show', 'upcoming');   // alte Links ?past=1
        $show = in_array($show, ['upcoming', 'past', 'all'], true) ? $show : 'upcoming';

        $gigs = Gig::query()
            ->with('band')
            ->whereIn('band_id', Band::query()->accessibleBy($user)->select('id'))
            ->when($band, fn ($query) => $query->where('band_id', $band->id))
            ->when($show === 'upcoming', fn ($query) => $query->upcoming())
            ->when($show === 'past', fn ($query) => $query->past())
            ->orderBy('date', $show === 'past' ? 'desc' : 'asc')
            ->orderBy('id')
            ->get();

        return view('band-manager.gigs.index', [
            'gigs' => $gigs,
            'bands' => Band::query()->accessibleBy($user)->orderBy('name')->get(),
            'bandId' => $band?->id,
            'show' => $show,
            'canManage' => $user->can('gigs.manage'),
            'canCreate' => $user->can('gigs.create'),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('gigs.create');

        $gig = new Gig([
            'band_id' => $request->integer('band') ?: null,
            'date' => (string) $request->query('date', ''),
            'status' => GigStatusEnum::Request,
            'booked_by' => $request->user()->can('gigs.manage') ? Gig::BOOKED_BY_AGENCY : Gig::BOOKED_BY_BAND,
        ]);

        return $this->form($request, $gig);
    }

    public function store(GigRequest $request): RedirectResponse
    {
        $user = $request->user();
        $attributes = $request->gigAttributes();

        $conflicts = $this->conflicts->check((int) $attributes['band_id'], $attributes['date']);
        if ($response = $this->unconfirmedConflict($request, $conflicts['blocking'])) {
            return $response;
        }

        $gig = Gig::query()->create($attributes + [
            'created_by' => $user->id,
            'booked_by' => $user->can('gigs.manage') ? Gig::BOOKED_BY_AGENCY : Gig::BOOKED_BY_BAND,
        ]);
        $gig->travelers()->sync($request->travelerIds());

        app(BlockCalendarDayForGigAction::class)->execute($gig);

        $this->logSaved('gig.created', $gig, $conflicts['blocking'] !== []);

        $message = $user->can('gigs.manage')
            ? 'Gig wurde angelegt.'
            : 'Show wurde angelegt. Das Day Sheet bekommst du in der Liste über „Day Sheet“.';

        if ($gig->isPast()) {
            return to_route('band-manager.gigs.index', ['show' => 'past'])
                ->with('success', $message)
                ->with('warning', $conflicts['hints'])
                ->with('info', 'Der Gig liegt in der Vergangenheit und steht deshalb unter „Vergangene“.');
        }

        return to_route('band-manager.gigs.index')->with('success', $message)->with('warning', $conflicts['hints']);
    }

    public function edit(Request $request, Gig $gig): View
    {
        Gate::authorize('update', $gig);

        return $this->form($request, $gig);
    }

    public function update(GigRequest $request, Gig $gig): RedirectResponse
    {
        $attributes = $request->gigAttributes();

        // Konfliktprüfung nur, wenn Datum oder Band geändert wurden
        $changed = $gig->date !== $attributes['date'] || $gig->band_id !== (int) $attributes['band_id'];
        $conflicts = $changed ? $this->conflicts->check((int) $attributes['band_id'], $attributes['date'], $gig->id) : ['blocking' => [], 'hints' => []];

        if ($response = $this->unconfirmedConflict($request, $conflicts['blocking'])) {
            return $response;
        }

        $gig->update($attributes);
        $gig->travelers()->sync($request->travelerIds());
        app(BlockCalendarDayForGigAction::class)->execute($gig);

        $this->logSaved('gig.updated', $gig, $conflicts['blocking'] !== []);

        return to_route('band-manager.gigs.index')->with('success', 'Gig wurde gespeichert.')->with('warning', $conflicts['hints']);
    }

    /**
     * Verknüpfte Verträge bleiben bei der Band, nur die Verknüpfung fällt weg (Fremdschlüssel).
     */
    public function destroy(Gig $gig): RedirectResponse
    {
        Gate::authorize('delete', $gig);

        $gig->delete();

        app(RecordActivityAction::class)->execute('gig.deleted', ['gig_id' => $gig->id, 'band_id' => $gig->band_id]);

        return to_route('band-manager.gigs.index')->with('success', 'Gig wurde gelöscht.');
    }

    /**
     * Day Sheet als PDF – für alle mit Zugriff auf die Band.
     */
    public function pdf(Gig $gig, DaySheet $daySheet): Response
    {
        Gate::authorize('view', $gig);

        $pdf = $daySheet->render($gig);

        app(RecordActivityAction::class)->execute('gig.pdf', ['gig_id' => $gig->id, 'band_id' => $gig->band_id]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$daySheet->fileName($gig).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function form(Request $request, Gig $gig): View
    {
        $user = $request->user();
        $bands = Band::query()->accessibleBy($user)->orderBy('name')->get();

        return view('band-manager.gigs.form', [
            'gig' => $gig,
            'bands' => $bands,
            'teamByBand' => TeamMember::query()
                ->whereIn('band_id', $bands->modelKeys())
                ->orderByRaw('type = ?', [TeamMember::TYPE_CREW])
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->groupBy('band_id'),
            'travelerIds' => array_map('intval', (array) old('travelers', $gig->exists ? $gig->travelers()->pluck('bm_team_members.id')->all() : [])),
            'statuses' => GigStatusEnum::cases(),
            'cateringOptions' => config('band-manager.gigs.catering_options'),
            'canManage' => $user->can('gigs.manage'),
            'conflict' => session('gigConflict'),
        ]);
    }

    /**
     * Blockierender Konflikt ohne Bestätigung → zurück zum Formular, Eingaben bleiben erhalten.
     *
     * @param  list<string>  $blocking
     */
    private function unconfirmedConflict(GigRequest $request, array $blocking): ?RedirectResponse
    {
        if ($blocking === []) {
            return null;
        }

        $key = $request->integer('band_id').'|'.$request->input('date');

        if ($request->input('confirm_conflict') === '1' && $request->input('confirm_key') === $key) {
            return null;
        }

        return back()
            ->withInput($request->except(['confirm_conflict', 'confirm_key']))
            ->with('gigConflict', ['messages' => $blocking, 'key' => $key]);
    }

    private function logSaved(string $action, Gig $gig, bool $conflictOverridden): void
    {
        app(RecordActivityAction::class)->execute($action, ['gig_id' => $gig->id, 'band_id' => $gig->band_id]);

        if ($conflictOverridden) {
            app(RecordActivityAction::class)->execute('gig.conflict_overridden', ['gig_id' => $gig->id, 'band_id' => $gig->band_id, 'date' => $gig->date]);
        }
    }

    private function bandFilter(Request $request): ?Band
    {
        if (! $request->filled('band')) {
            return null;
        }

        $band = Band::query()->findOrFail($request->integer('band'));
        Gate::authorize('access', $band);

        return $band;
    }
}
