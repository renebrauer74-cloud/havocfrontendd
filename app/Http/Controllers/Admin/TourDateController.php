<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Website\ListArtistsAlphabeticallyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TourDateRequest;
use App\Models\TourDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TourDateController extends Controller
{
    public function index(): View
    {
        return $this->form();
    }

    public function edit(TourDate $tourDate): View
    {
        return $this->form($tourDate);
    }

    public function store(TourDateRequest $request): RedirectResponse
    {
        TourDate::create($request->validated());

        return to_route('admin.dates.index')->with('status', 'Tourtermin gespeichert //');
    }

    public function update(TourDateRequest $request, TourDate $tourDate): RedirectResponse
    {
        $tourDate->update($request->validated());

        return to_route('admin.dates.index')->with('status', 'Tourtermin aktualisiert //');
    }

    public function destroy(TourDate $tourDate): RedirectResponse
    {
        $tourDate->delete();

        return to_route('admin.dates.index')->with('status', 'Tourtermin gelöscht //');
    }

    private function form(?TourDate $editing = null): View
    {
        return view('admin.dates', [
            'artists' => app(ListArtistsAlphabeticallyAction::class)->execute(),
            'dates' => TourDate::query()->with('artist')->get()->sortByShowDate(),
            'editing' => $editing,
        ]);
    }
}
