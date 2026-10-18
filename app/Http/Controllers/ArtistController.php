<?php

namespace App\Http\Controllers;

use App\Actions\Website\ResolveArtistLinksAction;
use App\Models\Artist;
use Illuminate\View\View;

class ArtistController extends Controller
{
    /**
     * Roster-Übersicht.
     */
    public function index(): View
    {
        return view('pages.roster', [
            'roster' => Artist::query()->with('media')->orderBy('id')->get(),
        ]);
    }

    /**
     * Profilseite mit Bio, Links, Galerie und Terminen.
     */
    public function show(Artist $artist, ResolveArtistLinksAction $resolveLinks): View
    {
        $artist->load(['media', 'galleryImages.media']);

        return view('pages.artist', [
            'artist' => $artist,
            'links' => $resolveLinks->execute($artist),
            'dates' => $artist->tourDates()->get()->sortByShowDate(),
        ]);
    }
}
