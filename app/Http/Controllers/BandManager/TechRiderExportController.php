<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use App\Models\BandManager\Band;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Export → Technischer Rider: alle Bands des Benutzers mit Download des Tech Riders als PDF.
 */
class TechRiderExportController extends Controller
{
    public function index(Request $request): View
    {
        return view('band-manager.exports.tech-rider', [
            'bands' => Band::query()->accessibleBy($request->user())->with('techRider')->orderBy('name')->get(),
        ]);
    }
}
