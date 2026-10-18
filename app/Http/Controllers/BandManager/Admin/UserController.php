<?php

namespace App\Http\Controllers\BandManager\Admin;

use App\Actions\BandManager\RecordActivityAction;
use App\Actions\BandManager\Users\CreateUserAction;
use App\Enums\BandManager\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BandManager\Admin\UserRequest;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('band-manager.admin.users.index', [
            'users' => User::query()->with('bands')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new User(['role' => UserRoleEnum::Artist, 'is_active' => true]));
    }

    public function store(UserRequest $request, CreateUserAction $createUser): RedirectResponse
    {
        $user = $createUser->execute($request->userAttributes(), $request->bandName());

        app(RecordActivityAction::class)->execute('user.created', ['user_id' => $user->id]);

        return to_route('band-manager.admin.users.index')->with('success', 'Benutzer wurde angelegt.');
    }

    public function edit(User $user): View
    {
        return $this->form($user);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->userAttributes());
        $user->bands()->sync($user->isAdmin() ? [] : $request->bandIds());

        app(RecordActivityAction::class)->execute('user.updated', ['user_id' => $user->id]);

        return to_route('band-manager.admin.users.index')->with('success', 'Benutzer wurde gespeichert.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return to_route('band-manager.admin.users.index')->with('error', 'Du kannst dich nicht selbst löschen.');
        }

        $user->delete();

        app(RecordActivityAction::class)->execute('user.deleted', ['user_id' => $user->id]);

        return to_route('band-manager.admin.users.index')->with('success', 'Benutzer wurde gelöscht.');
    }

    private function form(User $user): View
    {
        return view('band-manager.admin.users.form', [
            'user' => $user,
            // Zugänge (Bandmitglied, Team, Fotograf) legt die Band selbst an
            'roles' => $user->exists ? UserRoleEnum::cases() : [UserRoleEnum::Admin, UserRoleEnum::Artist],
            'bands' => Band::query()->orderBy('name')->get(),
            'selectedBandIds' => $user->exists ? $user->bands()->pluck('id')->all() : [],
        ]);
    }
}
