<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Hash;

it('lists users with their bands', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);
    User::factory()->hasAttached($band)->create(['name' => 'Kim Kitten']);

    actingAsBandManager()
        ->get(route('band-manager.admin.users.index'))
        ->assertSuccessful()
        ->assertSee('Kim Kitten')
        ->assertSee('Iron Kittens');
});

it('shows the user forms', function () {
    $user = User::factory()->create();

    actingAsBandManager()->get(route('band-manager.admin.users.create'))->assertSuccessful();
    actingAsBandManager()->get(route('band-manager.admin.users.edit', $user))->assertSuccessful()->assertSee($user->email);
});

it('creates a user with bands', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.admin.users.store'), [
            'name' => 'Kim Kitten',
            'email' => 'Kim@Example.com',
            'password' => 'geheim1234',
            'role' => 'artist',
            'is_active' => '1',
            'bands' => [$band->id],
        ])
        ->assertRedirect(route('band-manager.admin.users.index'))
        ->assertSessionHas('success');

    $user = User::query()->where('email', 'kim@example.com')->sole();

    expect($user->is_active)->toBeTrue()
        ->and(Hash::check('geheim1234', $user->password))->toBeTrue()
        ->and($user->bands->modelKeys())->toBe([$band->id])
        ->and(ActivityLog::query()->where('action', 'user.created')->exists())->toBeTrue();
});

it('validates new users', function (array $data, string $field) {
    User::factory()->create(['email' => 'vergeben@example.com']);

    actingAsBandManager()
        ->post(route('band-manager.admin.users.store'), array_merge([
            'name' => 'Kim',
            'email' => 'kim@example.com',
            'password' => 'geheim1234',
            'role' => 'artist',
        ], $data))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid email' => [['email' => 'keine-mail'], 'email'],
    'taken email' => [['email' => 'Vergeben@example.com'], 'email'],
    'short password' => [['password' => 'kurz'], 'password'],
    'missing password' => [['password' => ''], 'password'],
    'unknown role' => [['role' => 'superuser'], 'role'],
    'unknown band' => [['bands' => [999]], 'bands.0'],
]);

it('keeps the password when the field stays empty', function () {
    $user = User::factory()->create(['password' => 'altes-passwort']);

    actingAsBandManager()
        ->put(route('band-manager.admin.users.update', $user), [
            'name' => 'Neuer Name',
            'email' => $user->email,
            'password' => '',
            'role' => 'artist',
        ])
        ->assertRedirect(route('band-manager.admin.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Neuer Name')
        ->and($user->is_active)->toBeFalse()
        ->and(Hash::check('altes-passwort', $user->password))->toBeTrue();
});

it('does not let admins lock themselves out', function () {
    $admin = User::factory()->admin()->create();

    actingAsBandManager($admin)
        ->put(route('band-manager.admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'artist',
        ]);

    expect($admin->fresh())
        ->role->toBe(User::ROLE_ADMIN)
        ->is_active->toBeTrue();
});

it('deletes a user', function () {
    $user = User::factory()->create();

    actingAsBandManager()
        ->delete(route('band-manager.admin.users.destroy', $user))
        ->assertRedirect(route('band-manager.admin.users.index'));

    $this->assertModelMissing($user);
});

it('does not let admins delete themselves', function () {
    $admin = User::factory()->admin()->create();

    actingAsBandManager($admin)
        ->delete(route('band-manager.admin.users.destroy', $admin))
        ->assertSessionHas('error', 'Du kannst dich nicht selbst löschen.');

    $this->assertModelExists($admin);
});
