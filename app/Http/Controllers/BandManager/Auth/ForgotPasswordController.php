<?php

namespace App\Http\Controllers\BandManager\Auth;

use App\Actions\BandManager\Account\SendPasswordResetLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * „Passwort vergessen?“ – Link zum Zurücksetzen per E-Mail anfordern.
 */
class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('band-manager.auth.forgot-password');
    }

    /**
     * Immer dieselbe Antwort – so lässt sich nicht ausprobieren, welche Adressen einen Login haben.
     */
    public function store(ForgotPasswordRequest $request, SendPasswordResetLinkAction $sendResetLink): RedirectResponse
    {
        $sendResetLink->execute($request->validated('email'));

        return to_route('band-manager.password.request')->with('success', 'Falls es zu dieser E-Mail-Adresse einen Login gibt, haben wir dir gerade einen Link geschickt. Schau bitte auch im Spam-Ordner nach.');
    }
}
