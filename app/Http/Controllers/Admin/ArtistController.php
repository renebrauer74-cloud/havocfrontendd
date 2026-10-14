<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArtistRequest;
use App\Models\Artist;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function index(): View
    {
        return $this->form();
    }

    public function edit(Artist $artist): View
    {
        return $this->form($artist);
    }

    public function store(ArtistRequest $request): RedirectResponse
    {
        $this->save(new Artist, $request);

        return to_route('admin.artists.index')->with('status', 'Künstlerprofil gespeichert //');
    }

    public function update(ArtistRequest $request, Artist $artist): RedirectResponse
    {
        $this->save($artist, $request);

        return to_route('admin.artists.index')->with('status', 'Künstlerprofil aktualisiert //');
    }

    /**
     * Löscht den Künstler inkl. Tourdaten und Galerie (siehe Artist::booted()).
     */
    public function destroy(Artist $artist): RedirectResponse
    {
        $artist->delete();

        return to_route('admin.artists.index')->with('status', 'Künstlerprofil gelöscht //');
    }

    private function form(?Artist $editing = null): View
    {
        return view('admin.artists', [
            'artists' => Artist::alphabetical(),
            'editing' => $editing,
        ]);
    }

    private function save(Artist $artist, ArtistRequest $request): void
    {
        $artist->fill($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            $artist->replaceImage($request->file('image'));
        }

        $artist->save();
    }
}
