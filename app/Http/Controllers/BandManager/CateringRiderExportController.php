<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Export → Catering Rider: alle Bands des Benutzers mit Download des Catering Riders als PDF.
 */
class CateringRiderExportController extends Controller
{
    public function index(Request $request): View
    {
        return view('band-manager.exports.catering-rider', [
            'bands' => Band::query()->accessibleBy($request->user())->with('cateringRider')->orderBy('name')->get(),
        ]);
    }
}
