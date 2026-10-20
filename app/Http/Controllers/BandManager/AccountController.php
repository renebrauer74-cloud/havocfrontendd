<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\Account\UpdateAccountAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Enums\BandManager\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\AccountRequest;
use App\Services\BandManager\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mein Konto: Name, E-Mail und Passwort ändert jeder Login selbst, den Bandnamen nur der Haupt-Login der Band.
 * E-Mail: AccountEmailController, Passwort: AccountPasswordController.
 */
class AccountController extends Controller
{
    public function edit(Request $request, Impersonation $impersonation): View
    {
        $user = $request->user();

        return view('band-manager.account.edit', [
            'user' => $user,
            'bands' => $user->role === UserRoleEnum::Artist ? $user->bands()->orderBy('name')->get() : collect(),
            'isImpersonating' => $impersonation->isActive(),
            'passwordMin' => (int) config('band-manager.account.password_min'),
        ]);
    }

    public function update(AccountRequest $request, UpdateAccountAction $updateAccount): RedirectResponse
    {
        $updateAccount->execute($request->user(), $request->validated('name'), $request->bandNames());

        app(RecordActivityAction::class)->execute('account.updated');

        return to_route('band-manager.account.edit')->with('success', 'Deine Daten wurden gespeichert.');
    }
}
