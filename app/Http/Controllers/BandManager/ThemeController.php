<?php

namespace App\Http\Controllers\BandManager;

use App\Http\Controllers\Controller;
use App\Models\BandManager\User;
use App\Services\BandManager\Impersonation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Speichert Hell-/Dunkelmodus für den eingeloggten Benutzer (Aufruf per fetch aus theme.js).
 */
class ThemeController extends Controller
{
    public function __invoke(Request $request, Impersonation $impersonation): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(User::THEMES)],
        ]);

        // Beim "Einloggen als" nicht die Einstellung des Künstlers ändern
        if (! $impersonation->isActive()) {
            $request->user()->update(['theme' => $validated['theme']]);
        }

        return response()->json(['ok' => true, 'theme' => $validated['theme']]);
    }
}
