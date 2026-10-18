<?php

namespace App\Http\Controllers\BandManager;

use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Users\CreateBandAccessAction;
use App\Enums\BandManager\BandAreaEnum;
use App\Enums\BandManager\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\BandAccessRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Zugänge, die eine Band selbst vergibt (Bandmitglied, Team, Fotograf) – mit den Bereichen, die sie sehen bzw. bearbeiten dürfen.
 * Haupt-Login der Band und Agentur bleiben hier außen vor; die verwaltet nur der Admin.
 */
class BandAccessController extends Controller
{
    public function index(Band $band): View
    {
        Gate::authorize('access', $band);

        return view('band-manager.accesses.index', [
            'band' => $band,
            'accesses' => $band->users()->whereIn('role', UserRoleEnum::bandAccessCases())->orderBy('name')->get(),
            'areas' => BandAreaEnum::cases(),
        ]);
    }

    public function create(Band $band): View
    {
        Gate::authorize('access', $band);

        return $this->form($band, new User([
            'role' => UserRoleEnum::BandMember,
            'is_active' => true,
            'area_access' => array_map(fn ($access) => $access->value, UserRoleEnum::BandMember->defaultAreaAccess()),
        ]));
    }

    public function store(BandAccessRequest $request, Band $band, CreateBandAccessAction $createAccess): RedirectResponse
    {
        $user = $createAccess->execute($band, $request->userAttributes());

        app(RecordActivityAction::class)->execute('access.created', ['user_id' => $user->id, 'band_id' => $band->id]);

        return to_route('band-manager.accesses.index', $band)->with('success', "Zugang für {$user->name} wurde angelegt.");
    }

    public function edit(Band $band, User $user): View
    {
        $this->authorizeAccess($band, $user);

        return $this->form($band, $user);
    }

    public function update(BandAccessRequest $request, Band $band, User $user): RedirectResponse
    {
        $user->update($request->userAttributes());

        app(RecordActivityAction::class)->execute('access.updated', ['user_id' => $user->id, 'band_id' => $band->id]);

        return to_route('band-manager.accesses.index', $band)->with('success', "Zugang für {$user->name} wurde gespeichert.");
    }

    public function destroy(Band $band, User $user): RedirectResponse
    {
        $this->authorizeAccess($band, $user);

        $user->delete();

        app(RecordActivityAction::class)->execute('access.deleted', ['user_id' => $user->id, 'band_id' => $band->id]);

        return to_route('band-manager.accesses.index', $band)->with('success', "Zugang für {$user->name} wurde gelöscht.");
    }

    /**
     * Nur Zugänge dieser Band – nicht den eigenen Haupt-Login oder die Agentur (Zugehörigkeit prüft die Route-Bindung).
     */
    private function authorizeAccess(Band $band, User $user): void
    {
        Gate::authorize('access', $band);
        abort_unless($user->role->isBandAccess(), 404);
    }

    private function form(Band $band, User $user): View
    {
        return view('band-manager.accesses.form', [
            'band' => $band,
            'user' => $user,
            'roles' => UserRoleEnum::bandAccessCases(),
            'areas' => BandAreaEnum::cases(),
            'presets' => collect(UserRoleEnum::bandAccessCases())->mapWithKeys(fn (UserRoleEnum $role): array => [
                $role->value => array_map(fn ($access) => $access->value, $role->defaultAreaAccess()),
            ]),
        ]);
    }
}
