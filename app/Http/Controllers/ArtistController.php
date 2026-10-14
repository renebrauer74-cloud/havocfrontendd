<?php

namespace App\Http\Controllers;

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
            'roster' => Artist::query()->orderBy('id')->get(),
        ]);
    }

    /**
     * Profilseite mit Bio, Links, Galerie und Terminen.
     */
    public function show(Artist $artist): View
    {
        $artist->load('galleryImages');

        return view('pages.artist', [
            'artist' => $artist,
            'dates' => $artist->tourDates()->get()->sortByShowDate(),
        ]);
    }
}
