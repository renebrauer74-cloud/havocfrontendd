<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * /band-manager → Startseite passend zur Rolle.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect($request->user()->homeUrl());
    }

    /**
     * Startseite für Bands.
     */
    public function show(Request $request): View
    {
        return view('band-manager.home', ['user' => $request->user()]);
    }
}
