<?php

namespace App\Http\Controllers\BandManager\Admin;

use App\Actions\BandManager\DeleteBandAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\Admin\BandRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BandController extends Controller
{
    public function index(): View
    {
        return view('band-manager.admin.bands.index', [
            'bands' => Band::query()->with('users')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Band);
    }

    public function store(BandRequest $request): RedirectResponse
    {
        $band = Band::query()->create($request->bandAttributes());
        $band->users()->sync($request->userIds());

        app(RecordActivityAction::class)->execute('band.created', ['band_id' => $band->id]);

        return to_route('band-manager.admin.bands.index')->with('success', 'Band wurde angelegt.');
    }

    public function edit(Band $band): View
    {
        return $this->form($band);
    }

    public function update(BandRequest $request, Band $band): RedirectResponse
    {
        $band->update($request->bandAttributes());
        $band->users()->sync($request->userIds());

        app(RecordActivityAction::class)->execute('band.updated', ['band_id' => $band->id]);

        return to_route('band-manager.admin.bands.index')->with('success', 'Band wurde gespeichert.');
    }

    /**
     * Zugehörige Daten (Logins-Zuordnung, später Gigs, Kalender …) löscht die Datenbank per Fremdschlüssel mit.
     */
    public function destroy(Band $band, DeleteBandAction $deleteBand): RedirectResponse
    {
        $deleteBand->execute($band);

        app(RecordActivityAction::class)->execute('band.deleted', ['band_id' => $band->id]);

        return to_route('band-manager.admin.bands.index')->with('success', 'Band wurde gelöscht.');
    }

    private function form(Band $band): View
    {
        return view('band-manager.admin.bands.form', [
            'band' => $band,
            'users' => User::query()->orderBy('name')->get(),
            'selectedUserIds' => $band->exists ? $band->users()->pluck('id')->all() : [],
        ]);
    }
}
