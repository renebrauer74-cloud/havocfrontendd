<?php

use App\Http\Middleware\UseBandManagerGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Band-Manager: leere Formularfelder werden zu NULL – die Spalten dort sind nullable.
            Route::middleware(['web', UseBandManagerGuard::class, ConvertEmptyStringsToNull::class])
                ->prefix('band-manager')
                ->name('band-manager.')
                ->group(base_path('routes/band-manager.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Optionale Felder bleiben leere Strings – die Spalten sind NOT NULL DEFAULT ''.
        $middleware->remove(ConvertEmptyStringsToNull::class);

        // Hell/Dunkel-Cookie setzt der Band-Manager per JavaScript – daher unverschlüsselt.
        $middleware->encryptCookies(except: ['hv_theme']);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('band-manager', 'band-manager/*')
            ? route('band-manager.login')
            : route('admin.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('band-manager', 'band-manager/*')
            ? route('band-manager.index')
            : route('admin.artists.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'band-manager/api/*') || $request->expectsJson(),
        );

        // Fehlerseiten des Band-Managers im eigenen Layout statt im Website-Design
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('band-manager', 'band-manager/*') || $request->is('band-manager/api/*') || $request->expectsJson()) {
                return null;
            }

            return response()->view('band-manager.error', [
                'code' => $exception->getStatusCode(),
            ], $exception->getStatusCode(), $exception->getHeaders());
        });
    })->create();
