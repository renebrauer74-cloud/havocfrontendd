<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Account\ChangePasswordAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\AccountPasswordRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Mein Konto → Passwort ändern.
 */
class AccountPasswordController extends Controller
{
    public function update(AccountPasswordRequest $request, ChangePasswordAction $changePassword): RedirectResponse
    {
        $changePassword->execute($request->user(), $request->validated('password'));

        app(RecordActivityAction::class)->execute('account.password_changed');

        return to_route('band-manager.account.edit')->with('success', 'Dein Passwort wurde geändert.');
    }
}
