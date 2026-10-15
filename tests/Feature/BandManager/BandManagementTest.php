<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;

it('lists bands with their logins', function () {
    $artist = User::factory()->create(['name' => 'Kim Kitten']);
    Band::factory()->hasAttached($artist)->create(['name' => 'Iron Kittens']);

    actingAsBandManager()
        ->get(route('band-manager.admin.bands.index'))
        ->assertSuccessful()
        ->assertSee('Iron Kittens')
        ->assertSee('Kim Kitten');
});

it('shows the band forms', function () {
    $band = Band::factory()->create();

    actingAsBandManager()->get(route('band-manager.admin.bands.create'))->assertSuccessful();
    actingAsBandManager()->get(route('band-manager.admin.bands.edit', $band))->assertSuccessful()->assertSee($band->name);
});

it('creates a band and stores empty optional fields as null', function () {
    $artist = User::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.admin.bands.store'), [
            'name' => 'Iron Kittens',
            'genre' => '',
            'contact_email' => 'Booking@Kittens.de',
            'description' => '',
            'members' => [$artist->id],
        ])
        ->assertRedirect(route('band-manager.admin.bands.index'))
        ->assertSessionHas('success');

    $band = Band::query()->sole();

    expect($band)
        ->name->toBe('Iron Kittens')
        ->genre->toBeNull()
        ->description->toBeNull()
        ->contact_email->toBe('booking@kittens.de')
        ->and($band->users->modelKeys())->toBe([$artist->id])
        ->and(ActivityLog::query()->where('action', 'band.created')->exists())->toBeTrue();
});

it('validates bands', function (array $data, string $field) {
    actingAsBandManager()
        ->post(route('band-manager.admin.bands.store'), array_merge(['name' => 'Iron Kittens'], $data))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid contact email' => [['contact_email' => 'keine-mail'], 'contact_email'],
    'unknown member' => [['members' => [999]], 'members.0'],
]);

it('updates a band and its logins', function () {
    $band = Band::factory()->hasAttached(User::factory())->create();
    $newArtist = User::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.admin.bands.update', $band), [
            'name' => 'Neuer Name',
            'members' => [$newArtist->id],
        ])
        ->assertRedirect(route('band-manager.admin.bands.index'));

    expect($band->fresh()->name)->toBe('Neuer Name')
        ->and($band->users()->pluck('id')->all())->toBe([$newArtist->id]);
});

it('deletes a band including the login assignments', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager()
        ->delete(route('band-manager.admin.bands.destroy', $band))
        ->assertRedirect(route('band-manager.admin.bands.index'));

    $this->assertModelMissing($band);
    $this->assertModelExists($artist);
    $this->assertDatabaseCount('bm_band_user', 0);
});
