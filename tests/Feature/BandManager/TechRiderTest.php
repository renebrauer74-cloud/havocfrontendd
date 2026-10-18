<?php

use App\Actions\BandManager\DeleteBandAction;
use App\Actions\MediaLibrary\CreatePdfImageAction;
use App\Enums\BandManager\BacklineProviderEnum;
use App\Enums\BandManager\MicStandEnum;
use App\Enums\BandManager\MonitorTypeEnum;
use App\Enums\BandManager\UserRoleEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\TeamMember;
use App\Models\BandManager\TechRider;
use App\Models\BandManager\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('band-manager');
});

it('shows the rider creator heading with the tech rider in the menu', function () {
    actingAsBandManager()
        ->get(route('band-manager.tech-riders.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Rider Creator', 'Tech Rider']);
});

it('sends artists with one band straight to their tech rider', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.tech-riders.index'))
        ->assertRedirect(route('band-manager.tech-riders.band', $band));
});

it('prefills the line-up of a new rider from the band members', function () {
    $band = Band::factory()->create();
    TeamMember::factory()->for($band)->create(['type' => TeamMember::TYPE_MEMBER, 'first_name' => 'Lena', 'last_name' => 'Loud', 'stage_name' => null, 'position' => 'Gesang']);
    TeamMember::factory()->for($band)->create(['type' => TeamMember::TYPE_CREW, 'first_name' => 'Carl', 'last_name' => 'Crew']);

    actingAsBandManager()
        ->get(route('band-manager.tech-riders.band', $band))
        ->assertSuccessful()
        ->assertSee('value="Lena Loud"', false)
        ->assertSee('value="Gesang"', false)
        ->assertDontSee('value="Carl Crew"', false)
        ->assertSee('Vorlage einfügen (Rockband)');
});

it('saves the rider, keeps the order and drops empty rows', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.tech-riders.update', $band), [
            'contact_name' => 'Tom Technik',
            'contact_phone' => '+49 170 1234567',
            'brings_foh_engineer' => '1',
            'soundcheck_minutes' => '45',
            'stage_width' => '8.5',
            'line_up' => [['name' => 'Lena', 'instrument' => 'Gesang'], ['name' => '', 'instrument' => '']],
            'inputs' => [
                '17' => ['source' => 'Kick', 'mic' => 'Beta 91A', 'stand' => '', 'phantom' => '1', 'note' => ''],
                '3' => ['source' => '', 'mic' => '', 'stand' => MicStandEnum::Clamp->value, 'phantom' => '0', 'note' => ''],
                '9' => ['source' => 'Lead Vocals', 'mic' => 'SM58', 'stand' => MicStandEnum::TallBoom->value, 'phantom' => '0', 'note' => 'Funk'],
            ],
            'monitors' => [['who' => 'Drums', 'type' => MonitorTypeEnum::Drumfill->value, 'note' => 'Kick + Bass']],
            'backline' => [['item' => 'Bass-Amp', 'provider' => BacklineProviderEnum::Venue->value, 'note' => '']],
            'custom_sections' => [['title' => 'Pyro', 'content' => 'Zwei Funkenfontänen'], ['title' => '', 'content' => '']],
            'pa_notes' => 'Mind. 24 Kanäle',
        ])
        ->assertRedirect(route('band-manager.tech-riders.band', $band))
        ->assertSessionHas('success', 'Tech Rider wurde gespeichert.');

    $rider = $band->techRider()->first();

    expect($rider->contact_name)->toBe('Tom Technik')
        ->and($rider->brings_foh_engineer)->toBeTrue()
        ->and($rider->brings_monitor_engineer)->toBeFalse()
        ->and($rider->soundcheck_minutes)->toBe(45)
        ->and($rider->stage_width)->toBe('8.5')
        ->and($rider->line_up)->toBe([['name' => 'Lena', 'instrument' => 'Gesang']])
        ->and($rider->inputs)->toBe([
            ['source' => 'Kick', 'mic' => 'Beta 91A', 'stand' => '', 'note' => '', 'phantom' => true],
            ['source' => 'Lead Vocals', 'mic' => 'SM58', 'stand' => 'tall_boom', 'note' => 'Funk', 'phantom' => false],
        ])
        ->and($rider->monitors)->toHaveCount(1)
        ->and($rider->backline[0]['provider'])->toBe('venue')
        ->and($rider->custom_sections)->toBe([['title' => 'Pyro', 'content' => 'Zwei Funkenfontänen']])
        ->and(ActivityLog::query()->where('action', 'tech_rider.saved')->exists())->toBeTrue();
});

it('rejects unknown stands, monitor types and providers', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.tech-riders.update', $band), [
            'inputs' => [['source' => 'Kick', 'stand' => 'gaffa']],
            'monitors' => [['who' => 'Drums', 'type' => 'laut']],
            'backline' => [['item' => 'Amp', 'provider' => 'nachbar']],
        ])
        ->assertSessionHasErrors(['inputs.0.stand', 'monitors.0.type', 'backline.0.provider']);

    expect(TechRider::query()->count())->toBe(0);
});

it('uploads, shows and removes the stage plot', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);

    actingAsBandManager()
        ->put(route('band-manager.tech-riders.update', $band), ['stage_plot' => UploadedFile::fake()->image('plan.png', 800, 600)])
        ->assertSessionHasNoErrors();

    $media = $band->techRider()->first()->getFirstMedia(MediaCollectionEnum::StagePlots->value);

    expect($media->file_name)->toBe('Buehnenplan_Iron-Kittens.png');

    actingAsBandManager()->get(route('band-manager.tech-riders.stage-plot', $band))->assertSuccessful()->assertHeader('Content-Type', 'image/png');

    actingAsBandManager()->put(route('band-manager.tech-riders.update', $band), ['remove_stage_plot' => '1']);

    expect($band->techRider()->first()->getFirstMedia(MediaCollectionEnum::StagePlots->value))->toBeNull();
});

it('only accepts png or jpg as stage plot', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.tech-riders.update', $band), ['stage_plot' => UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors(['stage_plot' => 'Der Bühnenplan muss ein PNG- oder JPG-Bild sein (PDF bitte unter „Dokumente“ hochladen).']);
});

it('downloads the tech rider as pdf once it is saved', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);

    actingAsBandManager()
        ->get(route('band-manager.tech-riders.pdf', $band))
        ->assertRedirect(route('band-manager.tech-riders.band', $band));

    TechRider::factory()->for($band)->create(['custom_sections' => [['title' => 'Pyro', 'content' => '**Funken**']]]);

    $response = actingAsBandManager()->get(route('band-manager.tech-riders.pdf', $band));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('TechRider_Iron-Kittens.pdf');

    expect(substr((string) $response->getContent(), 0, 4))->toBe('%PDF')
        ->and(ActivityLog::query()->where('action', 'tech_rider.pdf')->exists())->toBeTrue();
});

it('shows the band logo centered at the top of the pdf', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);
    $band->profile->fill(['band_id' => $band->id])->save();
    $band->profile->addMedia(UploadedFile::fake()->image('logo.webp', 1200, 400))->toMediaCollection(MediaCollectionEnum::BandLogos->value);
    $rider = TechRider::factory()->for($band)->create();

    $logo = $band->profile->getFirstMedia(MediaCollectionEnum::BandLogos->value);
    $html = view('band-manager.riders.tech.pdf', [
        'rider' => $rider,
        'band' => $band,
        'bandLogo' => app(CreatePdfImageAction::class)->execute($logo, MediaConversionEnum::PdfLogo->value),
        'stagePlot' => null,
        'providers' => BacklineProviderEnum::cases(),
    ])->render();

    expect($logo->hasGeneratedConversion(MediaConversionEnum::PdfLogo->value))->toBeTrue()
        ->and($html)->toContain('<img src="data:image/png;base64,')
        ->and($html)->toContain('width="230" height="77"')
        ->and($html)->toContain('TECHNICAL RIDER');

    actingAsBandManager()->get(route('band-manager.tech-riders.pdf', $band))->assertSuccessful()->assertDownload('TechRider_Iron-Kittens.pdf');
});

it('shows the band name instead of a logo when there is none', function () {
    $rider = TechRider::factory()->create();

    $html = view('band-manager.riders.tech.pdf', ['rider' => $rider, 'band' => $rider->band, 'bandLogo' => null, 'stagePlot' => null, 'providers' => BacklineProviderEnum::cases()])->render();

    expect($html)->toContain('<div class="band">'.e($rider->band->name).'</div>')
        ->and($html)->toContain('Stellt der Veranstalter');
});

it('offers the pdf download in the overview once the rider is saved', function () {
    $saved = TechRider::factory()->create();
    $new = Band::factory()->create();

    actingAsBandManager()
        ->get(route('band-manager.tech-riders.index'))
        ->assertSee(route('band-manager.tech-riders.pdf', $saved->band))
        ->assertDontSee(route('band-manager.tech-riders.pdf', $new));
});

it('keeps tech riders of other bands private', function () {
    $artist = User::factory()->create();
    $rider = TechRider::factory()->create();

    actingAsBandManager($artist)->get(route('band-manager.tech-riders.band', $rider->band))->assertForbidden();
    actingAsBandManager($artist)->get(route('band-manager.tech-riders.pdf', $rider->band))->assertForbidden();
    actingAsBandManager($artist)->get(route('band-manager.tech-riders.stage-plot', $rider->band))->assertForbidden();
    actingAsBandManager($artist)->put(route('band-manager.tech-riders.update', $rider->band), ['contact_name' => 'X'])->assertForbidden();
});

it('lets band accesses with view rights read but not edit the rider', function () {
    $band = Band::factory()->create();
    $crew = User::factory()->bandAccess(UserRoleEnum::Team, ['riders' => 'view'])->hasAttached($band)->create();

    actingAsBandManager($crew)->get(route('band-manager.tech-riders.band', $band))->assertSuccessful()->assertDontSee('Speichern');
    actingAsBandManager($crew)->put(route('band-manager.tech-riders.update', $band), ['contact_name' => 'X'])->assertForbidden();

    $photographer = User::factory()->bandAccess(UserRoleEnum::Photographer, ['gallery' => 'edit'])->hasAttached($band)->create();

    actingAsBandManager($photographer)->get(route('band-manager.tech-riders.index'))->assertForbidden();
});

it('deletes the tech rider together with the band', function () {
    $rider = TechRider::factory()->create();
    $rider->addMedia(UploadedFile::fake()->image('plan.png'))->toMediaCollection(MediaCollectionEnum::StagePlots->value);

    app(DeleteBandAction::class)->execute($rider->band);

    expect(TechRider::query()->count())->toBe(0)
        ->and(Storage::disk('band-manager')->allFiles())->toBe([]);
});
