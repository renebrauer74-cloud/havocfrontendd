<?php

use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Gate;

it('keeps bands out of the admin area', function (string $route) {
    actingAsBandManager(User::factory()->create())
        ->get(route($route))
        ->assertForbidden()
        ->assertSee('Keine Berechtigung.');
})->with(['band-manager.admin.dashboard', 'band-manager.admin.users.index', 'band-manager.admin.bands.create']);

it('grants permissions from the role matrix', function () {
    $artist = User::factory()->create();
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($artist)->allows('gigs.view'))->toBeTrue()
        ->and(Gate::forUser($artist)->allows('gigs.manage'))->toBeFalse()
        ->and(Gate::forUser($artist)->allows('band-manager.admin'))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('gigs.manage'))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('band-manager.admin'))->toBeTrue();
});

it('supports wildcard permissions', function () {
    config(['band-manager.permissions.artist' => ['gigs.*']]);
    $artist = User::factory()->create();

    expect($artist->hasPermission('gigs.create'))->toBeTrue()
        ->and($artist->hasPermission('gallery.view'))->toBeFalse();
});

it('lets bands access only their own bands', function () {
    $artist = User::factory()->create();
    $ownBand = Band::factory()->hasAttached($artist)->create();
    $otherBand = Band::factory()->create();

    expect(Gate::forUser($artist)->allows('access', $ownBand))->toBeTrue()
        ->and(Gate::forUser($artist)->allows('access', $otherBand))->toBeFalse()
        ->and(Gate::forUser(User::factory()->admin()->create())->allows('access', $otherBand))->toBeTrue();
});

it('renders band manager errors in its own layout', function () {
    actingAsBandManager()
        ->get('/band-manager/admin/bands/999/edit')
        ->assertNotFound()
        ->assertSee('Seite nicht gefunden.')
        ->assertSee('hv-sidebar', false);
});
