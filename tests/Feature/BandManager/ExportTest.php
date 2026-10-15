<?php

use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
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

it('exports the availabilities of a month as csv', function () {
    $kittens = Band::factory()->create(['name' => 'Iron Kittens']);
    $nails = Band::factory()->create(['name' => 'Rusty Nails']);
    Availability::factory()->for($kittens)->create(['date' => '2026-11-03', 'status' => 'red', 'note' => 'Urlaub']);
    Availability::factory()->for($nails)->create(['date' => '2026-11-01', 'status' => 'green']);
    Availability::factory()->for($nails)->create(['date' => '2026-12-01', 'status' => 'green']);

    $response = actingAsBandManager()
        ->get(route('band-manager.admin.availability-export.download', ['month' => '2026-11']))
        ->assertSuccessful()
        ->assertDownload('verfuegbarkeiten-2026-11.csv');

    expect($response->streamedContent())->toBe(
        "\xEF\xBB\xBF"
        ."Band;Datum;Status;Notiz\n"
        ."\"Iron Kittens\";03.11.2026;\"Termin nicht möglich\";Urlaub\n"
        ."\"Rusty Nails\";01.11.2026;Verfügbar;\n"
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
