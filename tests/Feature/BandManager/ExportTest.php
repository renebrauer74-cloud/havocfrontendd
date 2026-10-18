<?php

use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use App\Models\BandManager\TechRider;
use App\Models\BandManager\User;

it('shows the export overview with the csv export for the agency', function () {
    actingAsBandManager()
        ->get(route('band-manager.exports.index'))
        ->assertSuccessful()
        ->assertSee('Verfügbarkeiten (CSV)');
});

it('shows the export menu only when an export is available', function () {
    config(['band-manager.permissions.artist' => ['availability.view']]);
    $artist = User::factory()->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.calendar.index'))
        ->assertDontSee(route('band-manager.exports.index'));

    actingAsBandManager($artist)
        ->get(route('band-manager.exports.index'))
        ->assertForbidden();
});

it('shows a single highlighted export entry in the menu that leads to the tile page', function () {
    actingAsBandManager()
        ->get(route('band-manager.calendar.index'))
        ->assertSeeInOrder(['Rider Creator', 'href="'.route('band-manager.exports.index').'" class="hv-side-link hv-side-link--export"', 'Export', 'Administration'], false)
        ->assertDontSee('Pressekit zusammenstellen')
        ->assertDontSee('Verfügbarkeiten (CSV)');
});

it('marks the export entry as active on the export pages', function () {
    actingAsBandManager()
        ->get(route('band-manager.tech-rider-export.index'))
        ->assertSee('class="hv-side-link active hv-side-link--export"', false);
});

it('shows the tech rider pdf download of each own band on the export page', function () {
    $artist = User::factory()->create();
    $rider = TechRider::factory()->create();
    $rider->band->users()->attach($artist);
    $emptyBand = Band::factory()->hasAttached($artist)->create();
    $foreignRider = TechRider::factory()->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.tech-rider-export.index'))
        ->assertSuccessful()
        ->assertSee(route('band-manager.tech-riders.pdf', $rider->band))
        ->assertSee($emptyBand->name)
        ->assertDontSee(route('band-manager.tech-riders.pdf', $emptyBand))
        ->assertSee('Jetzt ausfüllen')
        ->assertDontSee($foreignRider->band->name);
});

it('keeps the tech rider export for users who may view riders', function () {
    config(['band-manager.permissions.artist' => ['availability.view']]);

    actingAsBandManager(User::factory()->create())
        ->get(route('band-manager.tech-rider-export.index'))
        ->assertForbidden();
});

it('exports the availabilities of a month as csv', function () {
    $kittens = Band::factory()->create(['name' => 'Iron Kittens']);
    $nails = Band::factory()->create(['name' => 'Rusty Nails']);
    Availability::factory()->for($kittens)->create(['date' => '2026-11-03', 'status' => 'red', 'note' => 'Urlaub']);
    Availability::factory()->for($nails)->create(['date' => '2026-11-01', 'status' => 'green']);
    Availability::factory()->for($nails)->create(['date' => '2026-11-04', 'status' => 'pink', 'title' => 'Fotoshooting', 'start_time' => '10:00', 'end_time' => '14:00']);
    Availability::factory()->for($nails)->create(['date' => '2026-12-01', 'status' => 'green']);

    $response = actingAsBandManager()
        ->get(route('band-manager.admin.availability-export.download', ['month' => '2026-11']))
        ->assertSuccessful()
        ->assertDownload('verfuegbarkeiten-2026-11.csv');

    expect($response->streamedContent())->toBe(
        "\xEF\xBB\xBF"
        ."Band;Datum;Status;Titel;Uhrzeit;Notiz\n"
        ."\"Iron Kittens\";03.11.2026;\"Termin nicht möglich\";;ganztägig;Urlaub\n"
        ."\"Rusty Nails\";01.11.2026;Verfügbar;;ganztägig;\n"
        ."\"Rusty Nails\";04.11.2026;Sonstiges;Fotoshooting;10:00–14:00;\n"
    );
});

it('filters the csv export by band', function () {
    $kittens = Band::factory()->create(['name' => 'Iron Kittens']);
    Availability::factory()->for($kittens)->create(['date' => '2026-11-03']);
    Availability::factory()->for(Band::factory()->create(['name' => 'Rusty Nails']))->create(['date' => '2026-11-04']);

    $content = actingAsBandManager()
        ->get(route('band-manager.admin.availability-export.download', ['month' => '2026-11', 'band' => $kittens->id]))
        ->streamedContent();

    expect($content)->toContain('Iron Kittens')->not->toContain('Rusty Nails');
});

it('keeps the csv export for the agency', function () {
    actingAsBandManager(User::factory()->create())
        ->get(route('band-manager.admin.availability-export.index'))
        ->assertForbidden();
});
