<?php

namespace App\Http\Controllers\BandManager\Auth;

use App\Actions\BandManager\Account\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * „Passwort vergessen?“ – neues Passwort über den Link aus der E-Mail festlegen.
 */
class ResetPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('band-manager.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email'),
            'passwordMin' => (int) config('band-manager.account.password_min'),
        ]);
    }

    public function store(ResetPasswordRequest $request, ResetPasswordAction $resetPassword): RedirectResponse
    {
        $isReset = $resetPassword->execute(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('password'),
        );

        if (! $isReset) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Der Link ist abgelaufen oder ungültig. Bitte fordere über „Passwort vergessen?“ einen neuen an.',
            ]);
        }

        return to_route('band-manager.login')->with('success', 'Dein neues Passwort ist gespeichert. Du kannst dich jetzt damit anmelden.');
    }
}
