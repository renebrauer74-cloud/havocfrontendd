<?php

use App\Enums\BandManager\UserRoleEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

/**
 * Band mit Haupt-Login (Rolle „artist“).
 *
 * @return array{0: Band, 1: User}
 */
function bandWithOwner(): array
{
    $owner = User::factory()->create();

    return [Band::factory()->hasAttached($owner)->create(), $owner];
}

it('lists the accesses of a band', function () {
    [$band, $owner] = bandWithOwner();
    User::factory()->bandAccess(UserRoleEnum::Photographer, ['gallery' => 'edit'])->hasAttached($band)->create(['name' => 'Pia Photo']);
    User::factory()->bandAccess()->hasAttached(Band::factory())->create(['name' => 'Fremder Zugang']);

    actingAsBandManager($owner)
        ->get(route('band-manager.accesses.index', $band))
        ->assertSuccessful()
        ->assertSee('Pia Photo')
        ->assertSee('Fotograf')
        ->assertSee('Galerie')
        ->assertDontSee('Fremder Zugang')
        ->assertDontSee($owner->email);
});

it('links the accesses on the band page for the band owner only', function () {
    [$band, $owner] = bandWithOwner();
    $member = User::factory()->bandAccess(areaAccess: ['team' => 'view'])->hasAttached($band)->create();

    actingAsBandManager($owner)->get(route('band-manager.team.band', $band))->assertSee(route('band-manager.accesses.index', $band));
    actingAsBandManager($member)->get(route('band-manager.team.band', $band))->assertDontSee(route('band-manager.accesses.index', $band));
});

it('shows the access form with the role presets', function () {
    [$band, $owner] = bandWithOwner();

    actingAsBandManager($owner)
        ->get(route('band-manager.accesses.create', $band))
        ->assertSuccessful()
        ->assertSee('Bandmitglied')
        ->assertSee('Fotograf')
        ->assertSee('"photographer":{"team":"view","gallery":"edit"}', false);
});

it('creates an access with selected areas', function () {
    [$band, $owner] = bandWithOwner();

    actingAsBandManager($owner)
        ->post(route('band-manager.accesses.store', $band), [
            'name' => 'Pia Photo',
            'email' => 'Pia@Example.com',
            'password' => 'geheim1234',
            'role' => 'photographer',
            'is_active' => '1',
            'areas' => ['gallery' => 'edit', 'gigs' => 'view', 'calendar' => '', 'messages' => 'edit'],
        ])
        ->assertRedirect(route('band-manager.accesses.index', $band))
        ->assertSessionHas('success');

    $access = User::query()->where('email', 'pia@example.com')->sole();

    expect($access->role)->toBe(UserRoleEnum::Photographer)
        ->and($access->is_active)->toBeTrue()
        ->and(Hash::check('geheim1234', $access->password))->toBeTrue()
        ->and($access->area_access)->toBe(['gallery' => 'edit', 'gigs' => 'view', 'messages' => 'view'])
        ->and($access->bands->modelKeys())->toBe([$band->id])
        ->and(ActivityLog::query()->where('action', 'access.created')->exists())->toBeTrue();
});

it('validates accesses', function (array $data, string $field) {
    [$band, $owner] = bandWithOwner();
    User::factory()->create(['email' => 'vergeben@example.com']);

    actingAsBandManager($owner)
        ->post(route('band-manager.accesses.store', $band), array_merge([
            'name' => 'Pia',
            'email' => 'pia@example.com',
            'password' => 'geheim1234',
            'role' => 'photographer',
        ], $data))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'taken email' => [['email' => 'Vergeben@example.com'], 'email'],
    'short password' => [['password' => 'kurz'], 'password'],
    'artist role' => [['role' => 'artist'], 'role'],
    'admin role' => [['role' => 'admin'], 'role'],
    'unknown area' => [['areas' => ['admin' => 'edit']], 'areas'],
    'unknown access level' => [['areas' => ['gallery' => 'owner']], 'areas.gallery'],
]);

it('updates an access and keeps the password when the field stays empty', function () {
    [$band, $owner] = bandWithOwner();
    $access = User::factory()->bandAccess(areaAccess: ['gallery' => 'edit'])->hasAttached($band)->create(['password' => 'altes-passwort']);

    actingAsBandManager($owner)
        ->put(route('band-manager.accesses.update', [$band, $access]), [
            'name' => 'Neuer Name',
            'email' => $access->email,
            'password' => '',
            'role' => 'team',
            'areas' => ['calendar' => 'view'],
        ])
        ->assertRedirect(route('band-manager.accesses.index', $band));

    $access->refresh();

    expect($access->name)->toBe('Neuer Name')
        ->and($access->role)->toBe(UserRoleEnum::Team)
        ->and($access->is_active)->toBeFalse()
        ->and($access->area_access)->toBe(['calendar' => 'view'])
        ->and(Hash::check('altes-passwort', $access->password))->toBeTrue();
});

it('deletes an access', function () {
    [$band, $owner] = bandWithOwner();
    $access = User::factory()->bandAccess()->hasAttached($band)->create();

    actingAsBandManager($owner)
        ->delete(route('band-manager.accesses.destroy', [$band, $access]))
        ->assertRedirect(route('band-manager.accesses.index', $band));

    $this->assertModelMissing($access);
});

it('does not let bands manage logins of other bands', function () {
    [, $owner] = bandWithOwner();
    $otherBand = Band::factory()->create();
    $otherAccess = User::factory()->bandAccess()->hasAttached($otherBand)->create();

    actingAsBandManager($owner)->get(route('band-manager.accesses.index', $otherBand))->assertForbidden();
    actingAsBandManager($owner)->delete(route('band-manager.accesses.destroy', [$otherBand, $otherAccess]))->assertForbidden();

    $this->assertModelExists($otherAccess);
});

it('does not let bands edit the band owner or accesses outside the band', function () {
    [$band, $owner] = bandWithOwner();
    $secondOwner = User::factory()->hasAttached($band)->create();
    $foreignAccess = User::factory()->bandAccess()->hasAttached(Band::factory())->create();

    actingAsBandManager($owner)->get(route('band-manager.accesses.edit', [$band, $secondOwner]))->assertNotFound();
    actingAsBandManager($owner)->delete(route('band-manager.accesses.destroy', [$band, $secondOwner]))->assertNotFound();
    actingAsBandManager($owner)->get(route('band-manager.accesses.edit', [$band, $foreignAccess]))->assertNotFound();
    actingAsBandManager($owner)
        ->put(route('band-manager.accesses.update', [$band, $secondOwner]), [
            'name' => 'Gekapert', 'email' => $secondOwner->email, 'role' => 'team',
        ])
        ->assertForbidden();

    expect($secondOwner->fresh()->role)->toBe(UserRoleEnum::Artist);
});

it('does not let accesses manage logins', function () {
    [$band] = bandWithOwner();
    $member = User::factory()->bandAccess(areaAccess: ['team' => 'edit', 'calendar' => 'edit'])->hasAttached($band)->create();

    actingAsBandManager($member)->get(route('band-manager.accesses.index', $band))->assertForbidden();
    actingAsBandManager($member)->post(route('band-manager.accesses.store', $band), [])->assertForbidden();
});

it('grants accesses only the permissions of their areas', function () {
    $photographer = User::factory()->bandAccess(UserRoleEnum::Photographer, ['gallery' => 'edit', 'gigs' => 'view'])->create();

    expect(Gate::forUser($photographer)->allows('gallery.view'))->toBeTrue()
        ->and(Gate::forUser($photographer)->allows('gallery.edit'))->toBeTrue()
        ->and(Gate::forUser($photographer)->allows('gigs.view'))->toBeTrue()
        ->and(Gate::forUser($photographer)->allows('gigs.create'))->toBeFalse()
        ->and(Gate::forUser($photographer)->allows('availability.view'))->toBeFalse()
        ->and(Gate::forUser($photographer)->allows('documents.view'))->toBeFalse()
        ->and(Gate::forUser($photographer)->allows('logins.manage'))->toBeFalse();
});

it('keeps accesses out of areas that were not granted', function () {
    [$band] = bandWithOwner();
    $photographer = User::factory()->bandAccess(UserRoleEnum::Photographer, ['gallery' => 'view'])->hasAttached($band)->create();

    actingAsBandManager($photographer)->get(route('band-manager.gallery.band', $band))->assertSuccessful();
    actingAsBandManager($photographer)->get(route('band-manager.calendar.index'))->assertForbidden();
    actingAsBandManager($photographer)->get(route('band-manager.gigs.index'))->assertForbidden();
    actingAsBandManager($photographer)->get(route('band-manager.documents.index'))->assertForbidden();
});

it('sends accesses to their first granted area after login', function () {
    $photographer = User::factory()->bandAccess(UserRoleEnum::Photographer, ['gallery' => 'edit'])->create();
    $withoutAreas = User::factory()->bandAccess()->create();

    actingAsBandManager($photographer)->get(route('band-manager.index'))->assertRedirect(route('band-manager.gallery.index'));
    actingAsBandManager($withoutAreas)->get(route('band-manager.index'))->assertRedirect(route('band-manager.home'));
});

it('only offers admin and artist when the agency creates a user', function () {
    actingAsBandManager()
        ->post(route('band-manager.admin.users.store'), [
            'name' => 'Pia',
            'email' => 'pia@example.com',
            'password' => 'geheim1234',
            'role' => 'photographer',
        ])
        ->assertSessionHasErrors('role');
});
