<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Account\ConfirmEmailChangeAction;
use App\Actions\BandManager\Account\RequestEmailChangeAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\AccountEmailRequest;
use App\Models\BandManager\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mein Konto → E-Mail-Adresse ändern: erst Link an die neue Adresse, mit dem Klick darauf wird sie zum Login.
 */
class AccountEmailController extends Controller
{
    public function update(AccountEmailRequest $request, RequestEmailChangeAction $requestEmailChange): RedirectResponse
    {
        $email = $request->validated('email');

        $requestEmailChange->execute($request->user(), $email);

        app(RecordActivityAction::class)->execute('account.email_change_requested');

        return to_route('band-manager.account.edit')->with('info', "Wir haben dir eine E-Mail an {$email} geschickt. Klicke auf den Link darin, um die neue Adresse zu bestätigen – bis dahin meldest du dich weiter mit deiner bisherigen Adresse an.");
    }

    /**
     * Link aus der E-Mail – funktioniert auch ohne Login, die Signatur sichert ihn ab.
     */
    public function confirm(Request $request, User $user, ConfirmEmailChangeAction $confirmEmailChange): RedirectResponse
    {
        $email = (string) $request->query('email');

        $isValid = $request->hasValidSignature()
            && hash_equals(hash('sha256', $user->email), (string) $request->query('current'))
            && $user->is_active;

        if (! $isValid) {
            return $this->redirectAfterConfirmation()->with('error', 'Der Bestätigungslink ist abgelaufen oder wurde schon verwendet. Bitte ändere deine E-Mail-Adresse unter „Mein Konto“ erneut.');
        }

        if (User::query()->where('email', $email)->exists()) {
            return $this->redirectAfterConfirmation()->with('error', 'Diese E-Mail-Adresse wird inzwischen für einen anderen Login verwendet.');
        }

        $confirmEmailChange->execute($user, $email);

        app(RecordActivityAction::class)->execute('account.email_changed', ['user_id' => $user->id]);

        return $this->redirectAfterConfirmation()->with('success', "Deine E-Mail-Adresse wurde geändert. Du meldest dich ab jetzt mit {$email} an.");
    }

    private function redirectAfterConfirmation(): RedirectResponse
    {
        return Auth::guard('band-manager')->check()
            ? to_route('band-manager.account.edit')
            : to_route('band-manager.login');
    }
}
