<?php

namespace App\Http\Middleware;

use App\Services\BandManager\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deaktivierte Benutzer werden abgemeldet – außer der Admin sieht gerade ihre Ansicht ("Einloggen als").
 */
class EnsureBandManagerUserIsActive
{
    public function __construct(private Impersonation $impersonation) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('band-manager');

        if ($user !== null && ! $user->is_active && ! $this->impersonation->isActive()) {
            Auth::guard('band-manager')->logout();
            $request->session()->regenerate();

            return to_route('band-manager.login')->with('error', 'Dein Account ist deaktiviert.');
        }

        return $next($request);
    }
}
