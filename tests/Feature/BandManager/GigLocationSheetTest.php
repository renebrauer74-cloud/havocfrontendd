<?php

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\Gig;
use App\Models\BandManager\User;
use App\Services\BandManager\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('band-manager');
});

/**
 * Echtes PDF mit $pages Seiten – FPDI muss es lesen können.
 */
function locationSheetPdf(string $name = 'venue.pdf', int $pages = 1): UploadedFile
{
    $html = implode('<div style="page-break-before: always"></div>', array_map(fn (int $page): string => "<p>Location Info Seite {$page}</p>", range(1, $pages)));

    return UploadedFile::fake()->createWithContent($name, app(Pdf::class)->render($html));
}

function addLocationSheet(Gig $gig, UploadedFile $file): void
{
    $gig->addMedia($file)->toMediaCollection(MediaCollectionEnum::LocationSheets->value);
}

it('stores location sheets when saving a gig', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.gigs.store'), gigData($band, [
            'location_sheets' => [locationSheetPdf('Kulturfabrik.pdf'), UploadedFile::fake()->image('Lageplan.png', 800, 600)],
        ]))
        ->assertSessionHasNoErrors();

    $gig = Gig::sole();
    $sheets = $gig->getMedia(MediaCollectionEnum::LocationSheets->value);

    expect($sheets->pluck('file_name')->all())->toBe(['Kulturfabrik.pdf', 'Lageplan.png'])
        ->and($sheets->first()->getPathRelativeToRoot())->toStartWith('location-sheets/')
        ->and(ActivityLog::query()->where('action', 'gig.location_sheet_uploaded')->count())->toBe(2);

    actingAsBandManager()
        ->get(route('band-manager.gigs.edit', $gig))
        ->assertSuccessful()
        ->assertSeeInOrder(['Location Sheets', 'Kulturfabrik.pdf', 'Lageplan.png', 'noch 8 möglich']);
});

it('rejects location sheets that cannot go into the day sheet', function (Closure $file, string $message) {
    $gig = Gig::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.gigs.update', $gig), gigData($gig->band, ['date' => $gig->date, 'location_sheets' => [$file()]]))
        ->assertSessionHasErrors(['location_sheets.0' => $message]);

    expect($gig->getMedia(MediaCollectionEnum::LocationSheets->value))->toBeEmpty();
})->with([
    'unreadable pdf' => [
        fn () => UploadedFile::fake()->createWithContent('kaputt.pdf', "%PDF-1.4\n%Test\n"),
        '„kaputt.pdf“ lässt sich nicht ins Day Sheet übernehmen (z. B. passwortgeschützt). Bitte die Datei neu als PDF speichern oder als Bild hochladen.',
    ],
    'word file' => [
        fn () => UploadedFile::fake()->create('venue.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        'Location Sheets bitte als PDF, JPG oder PNG hochladen.',
    ],
    'too large' => [
        fn () => UploadedFile::fake()->image('riesig.jpg')->size(21 * 1024),
        'Ein Location Sheet ist zu groß (max. 20 MB).',
    ],
]);

it('limits the number of location sheets per gig', function () {
    config(['band-manager.gigs.location_sheets.max_files' => 2]);
    $gig = Gig::factory()->create();
    addLocationSheet($gig, UploadedFile::fake()->image('a.jpg'));

    actingAsBandManager()
        ->put(route('band-manager.gigs.update', $gig), gigData($gig->band, [
            'date' => $gig->date,
            'location_sheets' => [UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')],
        ]))
        ->assertSessionHasErrors(['location_sheets' => 'Zu viele Location Sheets – erlaubt sind insgesamt 2 pro Gig.']);

    expect($gig->getMedia(MediaCollectionEnum::LocationSheets->value))->toHaveCount(1);
});

it('appends location sheets at the end of the day sheet', function () {
    $gig = Gig::factory()->create(['venue' => 'Kulturfabrik']);
    $pdf = app(Pdf::class);
    $withoutSheets = $pdf->pageCount(actingAsBandManager()->get(route('band-manager.gigs.pdf', $gig))->getContent());

    addLocationSheet($gig, locationSheetPdf('Venue Info.pdf', 2));
    addLocationSheet($gig, UploadedFile::fake()->image('Lageplan.jpg', 1200, 800));

    $response = actingAsBandManager()
        ->get(route('band-manager.gigs.pdf', $gig->fresh()))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf');

    // 2 PDF-Seiten + 1 Seite für das Bild
    expect($pdf->pageCount($response->getContent()))->toBe($withoutSheets + 3);
});

it('names the location sheets in the day sheet', function () {
    $gig = Gig::factory()->create();

    $html = view('band-manager.gigs.pdf', [
        'gig' => $gig,
        'band' => $gig->band,
        'logo' => null,
        'team' => [],
        'catering' => [],
        'locationSheets' => ['Venue Info.pdf', 'Lageplan.jpg'],
        'locationSheetImages' => [['name' => 'Lageplan.jpg', 'image' => ['src' => 'data:image/jpeg;base64,', 'w' => 1200, 'h' => 800]]],
    ])->render();

    expect($html)
        ->toContain('Venue Info.pdf, Lageplan.jpg')
        ->toContain('Im Anhang am Ende dieses Day Sheets.')
        ->toContain('Location Sheet · Lageplan.jpg');
});

it('lets everyone with access to the band open location sheets', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    $agencyGig = Gig::factory()->for($band)->create();
    addLocationSheet($agencyGig, locationSheetPdf());
    $sheet = $agencyGig->getFirstMedia(MediaCollectionEnum::LocationSheets->value);

    actingAsBandManager($artist)
        ->get(route('band-manager.gigs.location-sheets.show', [$agencyGig, $sheet]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf');

    actingAsBandManager(User::factory()->create())
        ->get(route('band-manager.gigs.location-sheets.show', [$agencyGig, $sheet]))
        ->assertForbidden();

    actingAsBandManager()
        ->get(route('band-manager.gigs.location-sheets.show', [Gig::factory()->create(), $sheet]))
        ->assertNotFound();
});

it('deletes location sheets only for those who may edit the gig', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    $agencyGig = Gig::factory()->for($band)->create();
    addLocationSheet($agencyGig, locationSheetPdf());
    $sheet = $agencyGig->getFirstMedia(MediaCollectionEnum::LocationSheets->value);

    actingAsBandManager($artist)
        ->delete(route('band-manager.gigs.location-sheets.destroy', [$agencyGig, $sheet]))
        ->assertForbidden();

    actingAsBandManager()
        ->delete(route('band-manager.gigs.location-sheets.destroy', [$agencyGig, $sheet]))
        ->assertRedirect(route('band-manager.gigs.edit', $agencyGig))
        ->assertSessionHas('success', 'Location Sheet wurde gelöscht.');

    $this->assertModelMissing($sheet);
    Storage::disk('band-manager')->assertMissing($sheet->getPathRelativeToRoot());
});
