<?php

use App\Actions\BandManager\DeleteBandAction;
use App\Enums\BandManager\StagePlotElementEnum;
use App\Enums\BandManager\UserRoleEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\StagePlot;
use App\Models\BandManager\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('band-manager');
});

/**
 * Ein echtes PNG als data-URI – so schickt es der Designer beim Speichern.
 */
function stagePlotPngDataUri(): string
{
    $file = UploadedFile::fake()->image('plot.png', 60, 40);

    return 'data:image/png;base64,'.base64_encode((string) file_get_contents($file->getPathname()));
}

/**
 * Stage Plot mit gespeichertem Bild.
 */
function stagePlotWithImage(array $attributes = []): StagePlot
{
    $plot = StagePlot::factory()->create($attributes);
    $plot->addMediaFromBase64(stagePlotPngDataUri(), 'image/png')->toMediaCollection(MediaCollectionEnum::StagePlotImages->value);

    return $plot;
}

it('shows the stage plot designer below the catering rider in the rider creator menu', function () {
    actingAsBandManager()
        ->get(route('band-manager.stage-plots.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Rider Creator', 'Tech Rider', 'Catering Rider', 'Stage Plot Designer', 'Export']);
});

it('sends artists with one band straight to their stage plot', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.stage-plots.index'))
        ->assertRedirect(route('band-manager.stage-plots.band', $band));
});

it('shows every approved symbol in the palette of the designer', function () {
    $band = Band::factory()->create();

    $response = actingAsBandManager()
        ->get(route('band-manager.stage-plots.band', $band))
        ->assertSuccessful()
        ->assertSee('Vorlage: Rockband')
        ->assertSee('Speichern');

    foreach (StagePlotElementEnum::cases() as $element) {
        $response->assertSee('data-sp-add="'.$element->value.'"', false);

        if ($element !== StagePlotElementEnum::Text) {
            $response->assertSee('<symbol id="sp-'.$element->value.'"', false);
        }
    }
});

it('saves stage size, elements and the drawn png', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);

    actingAsBandManager()
        ->put(route('band-manager.stage-plots.update', $band), [
            'stage_width' => '10',
            'stage_depth' => '7.5',
            'items' => json_encode([
                ['type' => 'drums', 'x' => 500.4, 'y' => 150, 'w' => 220, 'h' => 179, 'rotation' => 0, 'label' => ' Drums '],
                ['type' => 'wedge', 'x' => 300, 'y' => 700, 'w' => 80, 'h' => 56, 'rotation' => 270, 'label' => 'Mon 1'],
                ['type' => 'text', 'x' => 100, 'y' => 100, 'w' => 0, 'h' => 24, 'rotation' => 0, 'label' => ''],
            ]),
            'notes' => 'Drum-Riser **40 cm** hoch',
            'image' => stagePlotPngDataUri(),
        ])
        ->assertRedirect(route('band-manager.stage-plots.band', $band))
        ->assertSessionHas('success', 'Stage Plot wurde gespeichert.');

    $plot = $band->stagePlot()->first();
    $media = $plot->getFirstMedia(MediaCollectionEnum::StagePlotImages->value);

    expect($plot->stage_width)->toBe('10.0')
        ->and($plot->stage_depth)->toBe('7.5')
        ->and($plot->items)->toBe([
            ['type' => 'drums', 'x' => 500, 'y' => 150, 'w' => 220, 'h' => 179, 'rotation' => 0, 'label' => 'Drums'],
            ['type' => 'wedge', 'x' => 300, 'y' => 700, 'w' => 80, 'h' => 56, 'rotation' => -90, 'label' => 'Mon 1'],
        ])
        ->and($media->file_name)->toBe('StagePlot_Iron-Kittens.png')
        ->and(ActivityLog::query()->where('action', 'stage_plot.saved')->exists())->toBeTrue();

    actingAsBandManager()->get(route('band-manager.stage-plots.image', $band))->assertSuccessful()->assertHeader('Content-Type', 'image/png');
});

it('keeps the saved png when no new image is sent', function () {
    $plot = stagePlotWithImage();

    actingAsBandManager()
        ->put(route('band-manager.stage-plots.update', $plot->band), ['stage_width' => '8', 'stage_depth' => '6', 'items' => '[]'])
        ->assertSessionHasNoErrors();

    expect($plot->fresh()->items)->toBe([])
        ->and($plot->fresh()->hasMedia(MediaCollectionEnum::StagePlotImages->value))->toBeTrue();
});

it('rejects unknown symbols, invalid stage sizes and images that are no png', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.stage-plots.update', $band), [
            'stage_width' => '1',
            'stage_depth' => '99',
            'items' => json_encode([['type' => 'pyro', 'x' => 1, 'y' => 1, 'w' => 1, 'h' => 1, 'rotation' => 0, 'label' => '']]),
            'image' => 'data:image/png;base64,'.base64_encode('<svg></svg>'),
        ])
        ->assertSessionHasErrors(['stage_width', 'stage_depth', 'items.0.type', 'image']);

    actingAsBandManager()
        ->put(route('band-manager.stage-plots.update', $band), ['stage_width' => '8', 'stage_depth' => '6', 'items' => 'kein json'])
        ->assertSessionHasErrors(['items']);

    expect(StagePlot::query()->count())->toBe(0);
});

it('downloads the stage plot as landscape pdf with legend once it has an image', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);

    actingAsBandManager()
        ->get(route('band-manager.stage-plots.pdf', $band))
        ->assertRedirect(route('band-manager.stage-plots.band', $band));

    stagePlotWithImage(['band_id' => $band->id]);

    $response = actingAsBandManager()->get(route('band-manager.stage-plots.pdf', $band));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('StagePlot_Iron-Kittens.pdf');

    expect(substr((string) $response->getContent(), 0, 4))->toBe('%PDF')
        ->and(ActivityLog::query()->where('action', 'stage_plot.pdf')->exists())->toBeTrue();
});

it('lists labeled elements and notes in the pdf', function () {
    $plot = stagePlotWithImage(['notes' => 'Riser **40 cm**', 'items' => [
        ['type' => 'microphone', 'x' => 400, 'y' => 450, 'w' => 60, 'h' => 60, 'rotation' => 0, 'label' => 'Lead Vocals'],
        ['type' => 'power', 'x' => 40, 'y' => 40, 'w' => 40, 'h' => 48, 'rotation' => 0, 'label' => ''],
        ['type' => 'text', 'x' => 400, 'y' => 40, 'w' => 0, 'h' => 24, 'rotation' => 0, 'label' => 'Backdrop'],
    ]]);

    $html = view('band-manager.riders.stage-plot.pdf', [
        'plot' => $plot,
        'band' => $plot->band,
        'bandLogo' => null,
        'image' => null,
        'legend' => [['element' => 'Mikrofon (Galgen)', 'label' => 'Lead Vocals']],
    ])->render();

    expect($html)->toContain('STAGE PLOT')
        ->and($html)->toContain('Bühne mind. 8 m × 6 m')
        ->and($html)->toContain('Mikrofon (Galgen)')
        ->and($html)->toContain('<strong>Lead Vocals</strong>')
        ->and($html)->toContain('<strong>40 cm</strong>');
});

it('shows pdf and png downloads of each own band on the export page', function () {
    $artist = User::factory()->create();
    $plot = stagePlotWithImage();
    $plot->band->users()->attach($artist);
    $emptyBand = Band::factory()->hasAttached($artist)->create();
    $foreignPlot = stagePlotWithImage();

    actingAsBandManager($artist)
        ->get(route('band-manager.stage-plot-export.index'))
        ->assertSuccessful()
        ->assertSee(route('band-manager.stage-plots.pdf', $plot->band))
        ->assertSee(route('band-manager.stage-plots.image', ['band' => $plot->band, 'download' => 1]))
        ->assertSee($emptyBand->name)
        ->assertDontSee(route('band-manager.stage-plots.pdf', $emptyBand))
        ->assertSee('Jetzt gestalten')
        ->assertDontSee($foreignPlot->band->name);
});

it('lists the stage plot below the export heading', function () {
    actingAsBandManager()
        ->get(route('band-manager.exports.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Technischer Rider', 'Catering Rider', 'Stage Plot', 'Verfügbarkeiten (CSV)'])
        ->assertSee(route('band-manager.stage-plot-export.index'));
});

it('keeps stage plots of other bands private', function () {
    $artist = User::factory()->create();
    $plot = stagePlotWithImage();

    actingAsBandManager($artist)->get(route('band-manager.stage-plots.band', $plot->band))->assertForbidden();
    actingAsBandManager($artist)->get(route('band-manager.stage-plots.pdf', $plot->band))->assertForbidden();
    actingAsBandManager($artist)->get(route('band-manager.stage-plots.image', $plot->band))->assertForbidden();
    actingAsBandManager($artist)->put(route('band-manager.stage-plots.update', $plot->band), ['stage_width' => '8', 'stage_depth' => '6'])->assertForbidden();
});

it('lets band accesses with view rights look at but not edit the stage plot', function () {
    $band = Band::factory()->create();
    $crew = User::factory()->bandAccess(UserRoleEnum::Team, ['riders' => 'view'])->hasAttached($band)->create();

    actingAsBandManager($crew)->get(route('band-manager.stage-plots.band', $band))->assertSuccessful()->assertDontSee('Speichern')->assertDontSee('Vorlage: Rockband');
    actingAsBandManager($crew)->put(route('band-manager.stage-plots.update', $band), ['stage_width' => '8', 'stage_depth' => '6'])->assertForbidden();
});

it('deletes the stage plot together with the band', function () {
    $plot = stagePlotWithImage();

    app(DeleteBandAction::class)->execute($plot->band);

    expect(StagePlot::query()->count())->toBe(0)
        ->and(Storage::disk('band-manager')->allFiles())->toBe([]);
});
