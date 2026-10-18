<?php

use App\Enums\BandManager\AvailabilityStatusEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use App\Models\BandManager\Gig;
use App\Models\BandManager\User;

/**
 * @return array<string, mixed>
 */
function gigData(Band $band, array $overrides = []): array
{
    return array_merge([
        'band_id' => $band->id,
        'date' => today()->addMonth()->format('Y-m-d'),
        'title' => 'Club Show',
        'venue' => 'Kulturfabrik',
        'city' => 'Köln',
        'fee' => '1.500,50',
        'status' => 'request',
    ], $overrides);
}

it('lists upcoming gigs for the agency including fees', function () {
    Gig::factory()->create(['title' => 'Release Party', 'fee' => 1200]);
    Gig::factory()->past()->create(['title' => 'Alte Show']);

    actingAsBandManager()
        ->get(route('band-manager.gigs.index'))
        ->assertSuccessful()
        ->assertSee('Release Party')
        ->assertSee('1.200,00 €')
        ->assertDontSee('Alte Show');
});

it('filters past gigs', function () {
    Gig::factory()->past()->create(['title' => 'Alte Show']);

    actingAsBandManager()
        ->get(route('band-manager.gigs.index', ['show' => 'past']))
        ->assertSee('Alte Show');
});

it('shows bands only their own gigs and hides agency fees', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    Gig::factory()->for($band)->create(['title' => 'Agentur-Gig', 'fee' => 999, 'notes' => 'intern']);
    Gig::factory()->for($band)->bandOwned()->create(['title' => 'Eigene Show', 'fee' => 123]);
    Gig::factory()->create(['title' => 'Fremde Band']);

    actingAsBandManager($artist)
        ->get(route('band-manager.gigs.index'))
        ->assertSuccessful()
        ->assertSee('Agentur-Gig')
        ->assertSee('Eigene Show')
        ->assertSee('123,00 €')
        ->assertDontSee('999,00 €')
        ->assertDontSee('intern')
        ->assertDontSee('Fremde Band');
});

it('forbids filtering by a foreign band', function () {
    actingAsBandManager(User::factory()->create())
        ->get(route('band-manager.gigs.index', ['band' => Band::factory()->create()->id]))
        ->assertForbidden();
});

it('creates a gig with german amounts and day sheet fields', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.gigs.store'), gigData($band, [
            'time_show' => '21:00',
            'time_get_in' => '15:30:00',
            'set_length' => '75',
            'catering' => 'buyout',
            'buyout_amount' => '15,50',
            'hotel_name' => 'Hotel Ibis',
        ]))
        ->assertRedirect(route('band-manager.gigs.index'))
        ->assertSessionHas('success', 'Gig wurde angelegt.');

    expect(Gig::sole())
        ->fee->toBe('1500.50')
        ->buyout_amount->toBe('15.50')
        ->time_show->toBe('21:00')
        ->time_get_in->toBe('15:30')
        ->set_length->toBe(75)
        ->hotel_name->toBe('Hotel Ibis')
        ->venue_street->toBeNull()
        ->booked_by->toBe(Gig::BOOKED_BY_AGENCY)
        ->and(ActivityLog::query()->where('action', 'gig.created')->exists())->toBeTrue();
});

it('drops the buy-out amount when there is no buy-out', function () {
    actingAsBandManager()->post(route('band-manager.gigs.store'), gigData(Band::factory()->create(), [
        'catering' => 'catering',
        'buyout_amount' => '15,00',
    ]));

    expect(Gig::sole()->buyout_amount)->toBeNull();
});

it('validates gigs', function (array $data, string $field) {
    actingAsBandManager()
        ->post(route('band-manager.gigs.store'), gigData(Band::factory()->create(), $data))
        ->assertSessionHasErrors($field);
})->with([
    'missing title' => [['title' => ''], 'title'],
    'invalid date' => [['date' => '2026-02-31'], 'date'],
    'fee not a number' => [['fee' => 'viel'], 'fee'],
    'unknown status' => [['status' => 'maybe'], 'status'],
    'invalid time' => [['time_show' => '25:00'], 'time_show'],
    'invalid buy-out' => [['catering' => 'both', 'buyout_amount' => 'abc'], 'buyout_amount'],
    'unknown catering' => [['catering' => 'buffet'], 'catering'],
    'set too long' => [['set_length' => '601'], 'set_length'],
]);

it('lets bands create shows only for their own bands', function () {
    $artist = User::factory()->create();
    $ownBand = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->post(route('band-manager.gigs.store'), gigData(Band::factory()->create()))
        ->assertSessionHasErrors(['band_id' => 'Bitte eine Band wählen.']);

    actingAsBandManager($artist)
        ->post(route('band-manager.gigs.store'), gigData($ownBand))
        ->assertSessionHas('success', 'Show wurde angelegt. Das Day Sheet bekommst du in der Liste über „Day Sheet“.');

    expect(Gig::sole())
        ->booked_by->toBe(Gig::BOOKED_BY_BAND)
        ->created_by->toBe($artist->id);
});

it('sends past gigs to the past list after saving', function () {
    actingAsBandManager()
        ->post(route('band-manager.gigs.store'), gigData(Band::factory()->create(), ['date' => today()->subDay()->format('Y-m-d')]))
        ->assertRedirect(route('band-manager.gigs.index', ['show' => 'past']))
        ->assertSessionHas('info');
});

it('blocks the calendar day of a confirmed gig', function () {
    $band = Band::factory()->create();

    actingAsBandManager()->post(route('band-manager.gigs.store'), gigData($band, ['date' => '2026-12-05', 'status' => 'confirmed', 'title' => 'Festival']));

    expect(Availability::sole())
        ->date->toBe('2026-12-05')
        ->status->toBe(AvailabilityStatusEnum::Red)
        ->note->toBe('Gig: Festival');
});

it('asks for confirmation when the band is not available', function () {
    $band = Band::factory()->create();
    Availability::factory()->for($band)->create(['date' => '2026-12-05', 'status' => 'red', 'note' => 'Urlaub']);

    actingAsBandManager()
        ->from(route('band-manager.gigs.create'))
        ->post(route('band-manager.gigs.store'), gigData($band, ['date' => '2026-12-05']))
        ->assertRedirect(route('band-manager.gigs.create'))
        ->assertSessionHas('gigConflict.messages', ['Am 05.12.2026 ist die Band im Kalender als „Termin nicht möglich“ eingetragen (Notiz: „Urlaub“).'])
        ->assertSessionHasInput('title', 'Club Show');

    expect(Gig::count())->toBe(0);

    actingAsBandManager()
        ->post(route('band-manager.gigs.store'), gigData($band, ['date' => '2026-12-05', 'confirm_conflict' => '1', 'confirm_key' => $band->id.'|2026-12-05']))
        ->assertSessionHas('success');

    expect(Gig::count())->toBe(1)
        ->and(ActivityLog::query()->where('action', 'gig.conflict_overridden')->exists())->toBeTrue();
});

it('detects double bookings but ignores cancelled gigs', function () {
    $band = Band::factory()->create();
    Gig::factory()->for($band)->cancelled()->create(['date' => '2026-12-05']);

    actingAsBandManager()->post(route('band-manager.gigs.store'), gigData($band, ['date' => '2026-12-05']))->assertSessionHas('success');

    actingAsBandManager()
        ->post(route('band-manager.gigs.store'), gigData($band, ['date' => '2026-12-05', 'title' => 'Zweiter Gig']))
        ->assertSessionHas('gigConflict.messages', ['Am 05.12.2026 ist bereits ein Gig eingetragen: „Club Show“.']);
});

it('shows a hint for orange days', function () {
    $band = Band::factory()->create();
    Availability::factory()->for($band)->create(['date' => '2026-12-05', 'status' => 'orange']);

    actingAsBandManager()
        ->post(route('band-manager.gigs.store'), gigData($band, ['date' => '2026-12-05']))
        ->assertSessionHas('warning', ['Am 05.12.2026 steht im Kalender „Muss besprochen werden“. Bitte mit der Band abstimmen.']);
});

it('does not re-check conflicts when date and band stay the same', function () {
    $band = Band::factory()->create();
    $gig = Gig::factory()->for($band)->create(['date' => '2026-12-05']);
    Availability::factory()->for($band)->create(['date' => '2026-12-05', 'status' => 'red']);

    actingAsBandManager()
        ->put(route('band-manager.gigs.update', $gig), gigData($band, ['date' => '2026-12-05', 'title' => 'Neuer Titel']))
        ->assertRedirect(route('band-manager.gigs.index'));

    expect($gig->fresh()->title)->toBe('Neuer Titel');
});

it('lets bands edit only their own shows', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    $agencyGig = Gig::factory()->for($band)->create();
    $ownShow = Gig::factory()->for($band)->bandOwned()->create();

    actingAsBandManager($artist)->get(route('band-manager.gigs.edit', $agencyGig))->assertForbidden();
    actingAsBandManager($artist)->put(route('band-manager.gigs.update', $agencyGig), gigData($band))->assertForbidden();
    actingAsBandManager($artist)->delete(route('band-manager.gigs.destroy', $agencyGig))->assertForbidden();

    actingAsBandManager($artist)->get(route('band-manager.gigs.edit', $ownShow))->assertSuccessful()->assertSee('Day Sheet (PDF)');
    actingAsBandManager($artist)->delete(route('band-manager.gigs.destroy', $ownShow))->assertRedirect(route('band-manager.gigs.index'));

    $this->assertModelMissing($ownShow);
});

it('shows the gig forms', function () {
    actingAsBandManager()->get(route('band-manager.gigs.create', ['date' => '2026-12-05']))->assertSuccessful()->assertSee('2026-12-05');
    actingAsBandManager()->get(route('band-manager.gigs.edit', Gig::factory()->create(['time_show' => '21:00:00'])))->assertSuccessful()->assertSee('21:00');
});

it('downloads the day sheet as pdf', function () {
    $band = Band::factory()->create(['name' => 'Rüdiger & Co']);
    $gig = Gig::factory()->for($band)->create([
        'date' => '2026-12-05',
        'city' => 'Köln',
        'time_show' => '21:00',
        'set_length' => 60,
        'hotel_name' => 'Hotel **Ibis**',
        'catering' => 'buyout',
        'buyout_amount' => 15,
    ]);

    $response = actingAsBandManager()
        ->get(route('band-manager.gigs.pdf', $gig))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('Havoc_DaySheet_2026-12-05_Ruediger-Co_Koeln.pdf');

    expect($response->getContent())->toStartWith('%PDF');
});

it('keeps day sheets of other bands private', function () {
    actingAsBandManager(User::factory()->create())
        ->get(route('band-manager.gigs.pdf', Gig::factory()->create()))
        ->assertForbidden();
});

it('shows gigs in the calendar and warns when a gig day is set to red', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    Gig::factory()->for($band)->create(['date' => '2026-11-10', 'title' => 'Festival', 'city' => 'Wacken']);
    Gig::factory()->for($band)->cancelled()->create(['date' => '2026-11-11']);

    actingAsBandManager($artist)
        ->getJson(route('band-manager.api.availability.index', ['band' => $band, 'start' => '2026-11-01', 'end' => '2026-12-01']))
        ->assertJsonCount(1)
        ->assertJsonPath('0.title', '🎤 Festival (Wacken)')
        ->assertJsonPath('0.extendedProps.type', 'gig');

    actingAsBandManager($artist)
        ->postJson(route('band-manager.api.availability.store', $band), ['from' => '2026-11-09', 'to' => '2026-11-11', 'status' => 'red'])
        ->assertJsonPath('warnings', ['Am 10.11.2026 ist bereits der Gig „Festival“ eingetragen. Bitte die Agentur informieren.']);
});

it('shows upcoming gigs on the dashboard', function () {
    Gig::factory()->create(['title' => 'Release Party']);

    actingAsBandManager()
        ->get(route('band-manager.admin.dashboard'))
        ->assertSee('Kommende Gigs')
        ->assertSee('Release Party');
});
