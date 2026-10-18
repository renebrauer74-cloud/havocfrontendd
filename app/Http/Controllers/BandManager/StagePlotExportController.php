<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Export → Stage Plot: alle Bands des Benutzers mit Download des Stage Plots als PDF und PNG.
 */
class StagePlotExportController extends Controller
{
    public function index(Request $request): View
    {
        return view('band-manager.exports.stage-plot', [
            'bands' => Band::query()->accessibleBy($request->user())->with('stagePlot.media')->orderBy('name')->get(),
        ]);
    }
}
