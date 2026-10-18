<?php

namespace App\Http\Controllers;

use App\Models\AgencyShow;
use Illuminate\View\View;

class AgencyShowController extends Controller
{
    /**
     * Exklusive Agentur-Shows, chronologisch.
     */
    public function index(): View
    {
        return view('shows.index', [
            'shows' => AgencyShow::query()->with('media')->get()->sortByShowDate(),
        ]);
    }

    public function show(AgencyShow $show): View
    {
        return view('shows.show', ['show' => $show]);
    }
}
