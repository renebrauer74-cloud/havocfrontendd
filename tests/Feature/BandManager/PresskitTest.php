<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\BandProfile;
use App\Models\BandManager\Document;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;
use App\Models\BandManager\User;
use App\Services\BandManager\Presskit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake('band-manager');
});

/**
 * Dateinamen im ZIP der Antwort (ohne Wurzelordner).
 *
 * @return list<string>
 */
function zipEntries(TestResponse $response): array
{
    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());

    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }
    $zip->close();

    return $entries;
}

function completeBand(): Band
{
    $band = Band::factory()->create(['name' => 'Rüdiger & Co', 'genre' => 'Metal']);
    BandProfile::query()->create([
        'band_id' => $band->id,
        'bio_de' => 'Laute **Band**',
        'bio_en' => 'Loud band',
        'links' => ['instagram' => 'https://www.instagram.com/ruediger/'],
    ]);
    TeamMember::factory()->for($band)->create(['first_name' => 'Kim']);
    TeamMember::factory()->for($band)->crew()->create(['first_name' => 'Sam']);
    Gig::factory()->for($band)->confirmed()->create(['date' => today()->addMonth()->format('Y-m-d'), 'title' => 'Festival']);
    Gig::factory()->for($band)->create(['date' => today()->addMonths(2)->format('Y-m-d'), 'title' => 'Nur Anfrage']);

    GalleryImage::factory()->for($band)->main()->withPhoto(800, 600)->create(['caption' => null, 'credit' => 'Max']);

    Document::factory()->for($band)->withFile('%PDF-1.4')->create(['title' => 'Tech Rider']);

    return $band;
}

it('shows what is available for the presskit', function () {
    $band = completeBand();

    actingAsBandManager()
        ->get(route('band-manager.presskit.create', ['band' => $band->id]))
        ->assertSuccessful()
        ->assertSee('Pressekit_Ruediger-Co_'.now()->format('Y-m-d'))
        ->assertSee('1 Person – nur Name, Instrument, Foto')
        ->assertSee('1 bestätigter Termin')
        ->assertSee('Tech Rider')
        ->assertDontSee('Kein Hauptbild festgelegt');
});

it('builds the presskit as zip with pdfs, photos and riders', function () {
    $band = completeBand();
    $rider = $band->documents()->sole();

    $response = actingAsBandManager()
        ->post(route('band-manager.presskit.store'), [
            'band_id' => $band->id,
            'lang' => ['de', 'en'],
            'inc' => ['cover', 'bio', 'members', 'links', 'contact', 'photos_pdf', 'photos_files', 'gigs_upcoming'],
            'riders' => [$rider->id],
        ])
        ->assertSuccessful()
        ->assertDownload('Pressekit_Ruediger-Co_'.now()->format('Y-m-d').'.zip');

    $root = 'Pressekit_Ruediger-Co_'.now()->format('Y-m-d').'/';

    expect(zipEntries($response))->toEqualCanonicalizing([
        $root.'01_Bandinfo_DE.pdf',
        $root.'01_Band-Info_EN.pdf',
        $root.'02_Fotos.pdf',
        $root.'03_Live-Termine.pdf',
        $root.'Fotos/01_Hauptbild.jpg',
        $root.'Fotos/Bildnachweise.txt',
        $root.'Rider/Technical-Rider-Tech-Rider.pdf',
    ])->and(ActivityLog::query()->where('action', 'presskit.created')->exists())->toBeTrue();
});

it('lists only confirmed gigs in the live dates', function () {
    $band = completeBand();
    $presskit = app(Presskit::class);

    $html = $presskit->renderLiveDates($band, $presskit->options(['inc' => ['gigs_upcoming', 'gigs_past']]), 'de');

    expect($html)->toContain('Festival')->not->toContain('Nur Anfrage')->toContain('Vergangene Shows');
});

it('never puts private data into the band info', function () {
    $band = completeBand();
    $band->teamMembers()->update(['phone' => '0171 999999', 'street' => 'Geheimweg 1']);
    $band->gigs()->update(['fee' => 4321]);

    $presskit = app(Presskit::class);
    $html = $presskit->renderBandInfo($band, $presskit->options(['inc' => ['members', 'member_bios', 'bio', 'links', 'contact']]), 'de');

    expect($html)->toContain('Laute <strong>Band</strong>')
        ->toContain('Kim')
        ->not->toContain('Sam')
        ->not->toContain('0171 999999')
        ->not->toContain('Geheimweg')
        ->not->toContain('4321')
        ->toContain('info@havoc-agency.com');
});

it('requires a selection and a language', function (array $data, string $error) {
    $band = completeBand();

    actingAsBandManager()
        ->post(route('band-manager.presskit.store'), ['band_id' => $band->id] + $data)
        ->assertRedirect(route('band-manager.presskit.create', ['band' => $band->id]))
        ->assertSessionHas('error', $error);
})->with([
    'nothing selected' => [[], 'Bitte mindestens einen Inhalt auswählen.'],
    'no language' => [['inc' => ['bio']], 'Bitte mindestens eine Sprache für die Bandinfo wählen.'],
]);

it('refuses an empty presskit', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.presskit.store'), ['band_id' => $band->id, 'inc' => ['photos_files']])
        ->assertSessionHas('error', 'Für die gewählten Inhalte gibt es noch keine Daten – das Pressekit wäre leer.');
});

it('ignores riders of other bands', function () {
    $band = completeBand();
    $foreign = Document::factory()->withFile()->create();

    $response = actingAsBandManager()->post(route('band-manager.presskit.store'), [
        'band_id' => $band->id, 'riders' => [$foreign->id], 'inc' => ['gigs_upcoming'],
    ]);

    expect(zipEntries($response))->toHaveCount(1);
});

it('lets bands build the presskit only for their own band', function () {
    $artist = User::factory()->create();
    $ownBand = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)->get(route('band-manager.presskit.create'))->assertSuccessful()->assertSee('name="band_id" value="'.$ownBand->id.'"', false);
    actingAsBandManager($artist)->get(route('band-manager.presskit.create', ['band' => Band::factory()->create()->id]))->assertForbidden();
    actingAsBandManager($artist)->post(route('band-manager.presskit.store'), ['band_id' => Band::factory()->create()->id])->assertForbidden();
});

it('shows the presskit tile on the export page', function () {
    actingAsBandManager(User::factory()->create())
        ->get(route('band-manager.exports.index'))
        ->assertSuccessful()
        ->assertSee('Pressekit zusammenstellen')
        ->assertDontSee('Verfügbarkeiten (CSV)');
});
