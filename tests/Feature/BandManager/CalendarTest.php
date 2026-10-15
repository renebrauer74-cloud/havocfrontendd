<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;

it('lists all bands for the agency', function () {
    Band::factory()->create(['name' => 'Iron Kittens']);
    Band::factory()->create(['name' => 'Rusty Nails']);

    actingAsBandManager()
        ->get(route('band-manager.calendar.index'))
        ->assertSuccessful()
        ->assertSee('Iron Kittens')
        ->assertSee('Rusty Nails');
});

it('sends a band with exactly one band straight to its calendar', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.calendar.index'))
        ->assertRedirect(route('band-manager.calendar.show', $band));
});

it('shows the calendar of an own band', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create(['name' => 'Iron Kittens']);

    actingAsBandManager($artist)
        ->get(route('band-manager.calendar.show', $band))
        ->assertSuccessful()
        ->assertSee('Iron Kittens – Verfügbarkeiten')
        ->assertSee('statusModal', false);
});

it('hides calendars of other bands', function () {
    actingAsBandManager(User::factory()->create())
        ->get(route('band-manager.calendar.show', Band::factory()->create()))
        ->assertForbidden();
});

it('returns availabilities as calendar events', function () {
    $band = Band::factory()->create();
    Availability::factory()->for($band)->create(['date' => '2026-11-03', 'status' => 'red', 'note' => 'Urlaub']);
    Availability::factory()->for($band)->create(['date' => '2026-12-24', 'status' => 'green']);

    actingAsBandManager()
        ->getJson(route('band-manager.api.availability.index', ['band' => $band, 'start' => '2026-11-01', 'end' => '2026-12-01']))
        ->assertSuccessful()
        ->assertJsonCount(1)
        ->assertJsonPath('0.start', '2026-11-03')
        ->assertJsonPath('0.display', 'background')
        ->assertJsonPath('0.title', 'Termin nicht möglich')
        ->assertJsonPath('0.extendedProps.note', 'Urlaub');
});

it('sets the status for a range of days', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    Availability::factory()->for($band)->create(['date' => '2026-11-02', 'status' => 'green']);

    actingAsBandManager($artist)
        ->postJson(route('band-manager.api.availability.store', $band), [
            'from' => '2026-11-01',
            'to' => '2026-11-03',
            'status' => 'orange',
            'note' => 'nur abends',
        ])
        ->assertSuccessful()
        ->assertJson(['ok' => true, 'updated' => 3]);

    expect($band->availabilities()->orderBy('date')->get(['date', 'status', 'note', 'updated_by'])->toArray())->toBe([
        ['date' => '2026-11-01', 'status' => 'orange', 'note' => 'nur abends', 'updated_by' => $artist->id],
        ['date' => '2026-11-02', 'status' => 'orange', 'note' => 'nur abends', 'updated_by' => $artist->id],
        ['date' => '2026-11-03', 'status' => 'orange', 'note' => 'nur abends', 'updated_by' => $artist->id],
    ])->and(ActivityLog::query()->where('action', 'availability.saved')->exists())->toBeTrue();
});

it('sets a single day when no end date is given', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->postJson(route('band-manager.api.availability.store', $band), ['from' => '2026-11-01', 'status' => 'green'])
        ->assertJson(['updated' => 1]);
});

it('validates calendar entries', function (array $data, string $field) {
    actingAsBandManager()
        ->postJson(route('band-manager.api.availability.store', Band::factory()->create()), array_merge([
            'from' => '2026-11-01',
            'to' => '2026-11-02',
            'status' => 'green',
        ], $data))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'unknown status' => [['status' => 'blue'], 'status'],
    'invalid date' => [['from' => '2026-02-30'], 'from'],
    'end before start' => [['to' => '2026-10-01'], 'to'],
    'range too large' => [['to' => '2028-01-01'], 'to'],
    'note too long' => [['note' => str_repeat('x', 501)], 'note'],
]);

it('clears a range of days', function () {
    $band = Band::factory()->create();
    Availability::factory()->for($band)->create(['date' => '2026-11-01']);
    Availability::factory()->for($band)->create(['date' => '2026-11-05']);

    actingAsBandManager()
        ->deleteJson(route('band-manager.api.availability.destroy', $band), ['from' => '2026-11-01', 'to' => '2026-11-03'])
        ->assertSuccessful()
        ->assertJson(['deleted' => 1]);

    expect($band->availabilities()->pluck('date')->all())->toBe(['2026-11-05']);
});

it('keeps other bands calendars read- and write-protected', function () {
    $artist = User::factory()->create();
    $otherBand = Band::factory()->create();

    actingAsBandManager($artist)
        ->getJson(route('band-manager.api.availability.index', $otherBand))
        ->assertForbidden();

    actingAsBandManager($artist)
        ->postJson(route('band-manager.api.availability.store', $otherBand), ['from' => '2026-11-01', 'status' => 'green'])
        ->assertForbidden();

    expect(Availability::count())->toBe(0);
});

it('requires the edit permission to change the calendar', function () {
    config(['band-manager.permissions.artist' => ['availability.view']]);
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.calendar.show', $band))
        ->assertSee('Nur Leseansicht.');

    actingAsBandManager($artist)
        ->postJson(route('band-manager.api.availability.store', $band), ['from' => '2026-11-01', 'status' => 'green'])
        ->assertForbidden();
});
