<?php

namespace App\Http\Controllers\BandManager\Auth;

use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Users\ResolveHomeUrlAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\LoginRequest;
use App\Services\BandManager\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('band-manager.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user('band-manager');
        $user->forceFill(['last_login_at' => now()])->save();

        app(RecordActivityAction::class)->execute('auth.login');

        return redirect()->intended(app(ResolveHomeUrlAction::class)->execute($user));
    }

    /**
     * Nur den Band-Manager-Login beenden – eine parallele Anmeldung im Website-Admin bleibt bestehen.
     */
    public function destroy(Request $request, Impersonation $impersonation): RedirectResponse
    {
        Auth::guard('band-manager')->logout();
        $impersonation->forget();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return to_route('band-manager.login');
    }
}
