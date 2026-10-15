<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alle Band-Manager-Routen arbeiten mit dem Guard "band-manager":
 * auth()->user(), @can und Gates sehen dort den Band-Manager-Benutzer, nicht den Website-Admin.
 */
class UseBandManagerGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('band-manager');

        return $next($request);
    }
}
