<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Alle Bands alphabetisch mit ihren Terminen – Bands ohne Termine fallen raus.
     */
    public function __invoke(): View
    {
        $artists = Artist::alphabetical()
            ->load('tourDates')
            ->filter(fn (Artist $artist): bool => $artist->tourDates->isNotEmpty())
            ->values();

        return view('pages.calendar', ['artists' => $artists]);
    }
}
