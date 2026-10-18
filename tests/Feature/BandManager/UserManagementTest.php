<?php

use App\Enums\BandManager\UserRoleEnum;
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

it('creates an artist together with their own band', function () {
    actingAsBandManager()
        ->post(route('band-manager.admin.users.store'), [
            'name' => 'Kim Kitten',
            'email' => 'Kim@Example.com',
            'password' => 'geheim1234',
            'role' => 'artist',
            'is_active' => '1',
            'band_name' => 'Iron Kittens',
        ])
        ->assertRedirect(route('band-manager.admin.users.index'))
        ->assertSessionHas('success');

    $user = User::query()->where('email', 'kim@example.com')->sole();
    $band = Band::query()->sole();

    expect($user->is_active)->toBeTrue()
        ->and(Hash::check('geheim1234', $user->password))->toBeTrue()
        ->and($band->name)->toBe('Iron Kittens')
        ->and($band->contact_email)->toBe('kim@example.com')
        ->and($user->bands->modelKeys())->toBe([$band->id])
        ->and(ActivityLog::query()->where('action', 'user.created')->exists())->toBeTrue()
        ->and(ActivityLog::query()->where('action', 'band.created')->exists())->toBeTrue();
});

it('creates an admin without a band', function () {
    actingAsBandManager()
        ->post(route('band-manager.admin.users.store'), [
            'name' => 'Alex Agentur',
            'email' => 'alex@example.com',
            'password' => 'geheim1234',
            'role' => 'admin',
            'band_name' => 'Wird ignoriert',
        ])
        ->assertRedirect(route('band-manager.admin.users.index'));

    $admin = User::query()->where('email', 'alex@example.com')->sole();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->bands)->toBeEmpty()
        ->and(Band::query()->exists())->toBeFalse();
});

it('removes band assignments when a user becomes admin', function () {
    $band = Band::factory()->create();
    $user = User::factory()->hasAttached($band)->create();

    actingAsBandManager()
        ->put(route('band-manager.admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'admin',
            'bands' => [$band->id],
        ])
        ->assertRedirect(route('band-manager.admin.users.index'));

    expect($user->fresh()->bands)->toBeEmpty();
});

it('updates the bands of an artist', function () {
    [$oldBand, $newBand] = Band::factory()->count(2)->create();
    $user = User::factory()->hasAttached($oldBand)->create();

    actingAsBandManager()
        ->put(route('band-manager.admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'artist',
            'bands' => [$newBand->id],
        ])
        ->assertRedirect(route('band-manager.admin.users.index'));

    expect($user->fresh()->bands->modelKeys())->toBe([$newBand->id]);
});

it('validates new users', function (array $data, string $field) {
    User::factory()->create(['email' => 'vergeben@example.com']);

    actingAsBandManager()
        ->post(route('band-manager.admin.users.store'), array_merge([
            'name' => 'Kim',
            'email' => 'kim@example.com',
            'password' => 'geheim1234',
            'role' => 'artist',
            'band_name' => 'Iron Kittens',
        ], $data))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid email' => [['email' => 'keine-mail'], 'email'],
    'taken email' => [['email' => 'Vergeben@example.com'], 'email'],
    'short password' => [['password' => 'kurz'], 'password'],
    'missing password' => [['password' => ''], 'password'],
    'unknown role' => [['role' => 'superuser'], 'role'],
    'missing band name' => [['band_name' => ''], 'band_name'],
    'too long band name' => [['band_name' => str_repeat('a', 151)], 'band_name'],
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
        ->role->toBe(UserRoleEnum::Admin)
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
