<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use App\Services\BandManager\Exports;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sammelstelle für alle Exporte (CSV, Pressekit …).
 */
class ExportController extends Controller
{
    public function index(Request $request, Exports $exports): View
    {
        $available = $exports->availableFor($request->user());

        abort_if($available === [], 403);

        return view('band-manager.exports.index', ['exports' => $available]);
    }
}
