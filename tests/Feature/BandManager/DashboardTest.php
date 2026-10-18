<?php

use App\Actions\BandManager\Availability\SummarizeAvailabilityAction;
use App\Actions\BandManager\RecordActivityAction;
use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use App\Models\BandManager\User;

it('shows stats and the latest activities', function () {
    $admin = User::factory()->admin()->create(['name' => 'Rene']);
    Band::factory()->count(2)->create();
    actingAsBandManager($admin);
    app(RecordActivityAction::class)->execute('band.created');

    $this->get(route('band-manager.admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Letzte Aktivitäten')
        ->assertSee('band.created')
        ->assertSee('Rene');
});

it('summarizes the availabilities of the next days per band', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);
    Availability::factory()->for($band)->create(['date' => today()->addDay()->format('Y-m-d'), 'status' => 'red']);
    Availability::factory()->for($band)->create(['date' => today()->addDays(2)->format('Y-m-d'), 'status' => 'red']);
    Availability::factory()->for($band)->create(['date' => today()->addYear()->format('Y-m-d'), 'status' => 'red']);

    expect(app(SummarizeAvailabilityAction::class)->execute(90))->toBe([$band->id => ['red' => 2]]);

    actingAsBandManager()
        ->get(route('band-manager.admin.dashboard'))
        ->assertSee('Verfügbarkeiten – nächste 90 Tage')
        ->assertSee('Iron Kittens');
});

it('shows who is available on a chosen day', function () {
    $kittens = Band::factory()->create(['name' => 'Iron Kittens']);
    Band::factory()->create(['name' => 'Rusty Nails']);
    Availability::factory()->for($kittens)->create(['date' => '2026-12-24', 'status' => 'green', 'note' => 'gerne']);

    actingAsBandManager()
        ->get(route('band-manager.admin.dashboard', ['date' => '2026-12-24']))
        ->assertSeeInOrder(['Iron Kittens', 'gerne', 'Verfügbar', 'Rusty Nails', 'Keine Angabe']);
});
