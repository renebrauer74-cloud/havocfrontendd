<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\News;
use App\Models\TourDate;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Startseite: Band-Karussell, die zwei neuesten News plus Archiv, die nächsten vier Termine.
     */
    public function __invoke(): View
    {
        $news = News::query()->with('media')->latest('id')->get();

        return view('pages.home', [
            'roster' => Artist::query()->with('media')->orderBy('id')->get(),
            'latestNews' => $news->take(2),
            'archiveNews' => $news->slice(2),
            'upcomingDates' => TourDate::query()->with('artist')->get()->sortByShowDate()->take(4),
        ]);
    }
}
