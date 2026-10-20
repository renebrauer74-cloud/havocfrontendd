<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;
use App\Services\BandManager\GigConflicts;

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

it('explains how to add entries on touch and mouse devices', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.calendar.show', $band))
        ->assertSee('hv-cal-hint', false)
        ->assertSee('Tippe auf einen Tag')
        ->assertSee('Tag gedrückt halten und weiterziehen')
        ->assertSee('Klicke auf einen Tag');
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
        ->assertJsonPath('0.title', 'Urlaub')
        ->assertJsonPath('0.extendedProps.statusLabel', 'Termin nicht möglich')
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
    'unknown status' => [['status' => 'purple'], 'status'],
    'invalid date' => [['from' => '2026-02-30'], 'from'],
    'end before start' => [['to' => '2026-10-01'], 'to'],
    'range too large' => [['to' => '2028-01-01'], 'to'],
    'note too long' => [['note' => str_repeat('x', 501)], 'note'],
    'other without title' => [['status' => 'pink'], 'title'],
    'title too long' => [['title' => str_repeat('x', 101)], 'title'],
    'missing start time' => [['all_day' => false], 'start_time'],
    'invalid start time' => [['all_day' => false, 'start_time' => '25:00'], 'start_time'],
    'end time before start time' => [['all_day' => false, 'start_time' => '18:00', 'end_time' => '17:00'], 'end_time'],
]);

it('saves a rehearsal with times', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->postJson(route('band-manager.api.availability.store', $band), [
            'from' => '2026-11-04',
            'status' => 'blue',
            'title' => 'Probe Setlist',
            'all_day' => false,
            'start_time' => '18:00',
            'end_time' => '21:30',
        ])
        ->assertSuccessful();

    $availability = $band->availabilities()->sole();

    expect($availability->status->value)->toBe('blue')
        ->and($availability->title)->toBe('Probe Setlist')
        ->and(substr($availability->start_time, 0, 5))->toBe('18:00')
        ->and(substr($availability->end_time, 0, 5))->toBe('21:30');
});

it('saves an other appointment with its own title as all-day entry', function () {
    $band = Band::factory()->create();
    Availability::factory()->for($band)->create(['date' => '2026-11-05', 'status' => 'blue', 'start_time' => '18:00', 'end_time' => '20:00']);

    actingAsBandManager()
        ->postJson(route('band-manager.api.availability.store', $band), [
            'from' => '2026-11-05',
            'status' => 'pink',
            'title' => 'Musikvideo-Dreh',
            'all_day' => true,
            'start_time' => '10:00',
        ])
        ->assertSuccessful();

    $availability = $band->availabilities()->sole();

    expect($availability->status->value)->toBe('pink')
        ->and($availability->title)->toBe('Musikvideo-Dreh')
        ->and($availability->start_time)->toBeNull()
        ->and($availability->end_time)->toBeNull();
});

it('returns time and title for the calendar cells', function () {
    $band = Band::factory()->create();
    Availability::factory()->for($band)->create(['date' => '2026-11-04', 'status' => 'pink', 'title' => 'Fotoshooting', 'start_time' => '10:00', 'end_time' => '14:00']);
    Availability::factory()->for($band)->create(['date' => '2026-11-05', 'status' => 'blue', 'start_time' => '18:00']);
    Availability::factory()->for($band)->create(['date' => '2026-11-06', 'status' => 'green', 'note' => 'nur abends']);

    actingAsBandManager()
        ->getJson(route('band-manager.api.availability.index', ['band' => $band, 'start' => '2026-11-01', 'end' => '2026-12-01']))
        ->assertSuccessful()
        ->assertJsonPath('0.title', 'Fotoshooting')
        ->assertJsonPath('0.color', '#ec4899')
        ->assertJsonPath('0.extendedProps.time', '10:00–14:00')
        ->assertJsonPath('0.extendedProps.startTime', '10:00')
        ->assertJsonPath('0.extendedProps.endTime', '14:00')
        ->assertJsonPath('1.title', 'Probe')
        ->assertJsonPath('1.color', '#3b82f6')
        ->assertJsonPath('1.extendedProps.time', 'ab 18:00')
        ->assertJsonPath('2.title', 'nur abends')
        ->assertJsonPath('2.extendedProps.time', null);
});

it('shows rehearsals and other appointments as hints when booking a gig', function () {
    $band = Band::factory()->create();
    Availability::factory()->for($band)->create(['date' => '2026-11-04', 'status' => 'pink', 'title' => 'Fotoshooting', 'start_time' => '10:00', 'end_time' => '14:00']);

    expect(app(GigConflicts::class)->check($band->id, '2026-11-04'))->toBe([
        'blocking' => [],
        'hints' => ['Am 04.11.2026 steht im Kalender „Fotoshooting (10:00–14:00 Uhr)“. Bitte mit der Band abstimmen.'],
    ]);
});

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
