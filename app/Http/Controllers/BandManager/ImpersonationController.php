<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Users\ResolveHomeUrlAction;
use App\Http\Controllers\Controller;
use App\Models\BandManager\User;
use App\Services\BandManager\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Einloggen als": Admin übernimmt die Ansicht eines Band-Logins und kehrt wieder zurück.
 */
class ImpersonationController extends Controller
{
    public function __construct(private Impersonation $impersonation) {}

    public function store(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return to_route('band-manager.admin.users.index')->with('error', 'Admin-Accounts können nicht übernommen werden.');
        }

        app(RecordActivityAction::class)->execute('user.impersonated', ['user_id' => $user->id]);

        $this->impersonation->start($request->user(), $user);

        return redirect(app(ResolveHomeUrlAction::class)->execute($user));
    }

    public function destroy(): RedirectResponse
    {
        if ($this->impersonation->stop() === null) {
            return to_route('band-manager.index');
        }

        return to_route('band-manager.admin.users.index')->with('success', 'Zurück im Admin-Konto.');
    }
}
