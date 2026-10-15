<?php

use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
use App\Models\BandManager\Gig;
use App\Models\BandManager\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('band-manager');
});

function pdf(string $name = 'vertrag.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, 120, 'application/pdf');
}

it('lets the agency upload a contract linked to a gig', function () {
    $band = Band::factory()->create();
    $gig = Gig::factory()->for($band)->create();

    actingAsBandManager()
        ->post(route('band-manager.documents.store'), [
            'type' => 'contract',
            'band_id' => $band->id,
            'gig_id' => $gig->id,
            'file' => pdf('Vertrag Stadtfest.pdf'),
            'filter' => $band->id,
        ])
        ->assertRedirect(route('band-manager.documents.index', ['band' => $band->id]))
        ->assertSessionHas('success', 'Vertrag wurde hinterlegt.');

    $document = Document::sole();

    expect($document)
        ->title->toBe('Vertrag Stadtfest')
        ->gig_id->toBe($gig->id)
        ->category->toBeNull()
        ->and(ActivityLog::query()->where('action', 'document.uploaded')->exists())->toBeTrue();

    Storage::disk('band-manager')->assertExists($document->storagePath());
});

it('lets bands upload riders for their own band only', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->post(route('band-manager.documents.store'), ['type' => 'rider', 'band_id' => $band->id, 'category' => 'technical', 'title' => 'Tech Rider', 'file' => pdf()])
        ->assertSessionHas('success', 'Rider wurde hochgeladen.');

    actingAsBandManager($artist)
        ->post(route('band-manager.documents.store'), ['type' => 'rider', 'band_id' => Band::factory()->create()->id, 'category' => 'technical', 'file' => pdf()])
        ->assertSessionHasErrors(['band_id' => 'Bitte eine Band wählen.']);

    expect(Document::sole())->type->toBe('rider')->category->toBe('technical')->title->toBe('Tech Rider');
});

it('keeps contracts for the agency', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->post(route('band-manager.documents.store'), ['type' => 'contract', 'band_id' => $band->id, 'file' => pdf()])
        ->assertForbidden();

    expect(Document::count())->toBe(0);
});

it('validates documents', function (array $data, string $field) {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.documents.store'), array_merge(['type' => 'rider', 'band_id' => $band->id, 'category' => 'technical', 'file' => pdf()], $data))
        ->assertSessionHasErrors($field);
})->with([
    'unknown type' => [['type' => 'invoice'], 'type'],
    'missing band' => [['band_id' => ''], 'band_id'],
    'unknown rider category' => [['category' => 'menu'], 'category'],
    'missing file' => [['file' => null], 'file'],
    'not a pdf' => [['file' => UploadedFile::fake()->image('rider.jpg')], 'file'],
    'too large' => [['file' => UploadedFile::fake()->create('big.pdf', 60 * 1024, 'application/pdf')], 'file'],
]);

it('rejects gigs of other bands', function () {
    actingAsBandManager()
        ->post(route('band-manager.documents.store'), [
            'type' => 'contract',
            'band_id' => Band::factory()->create()->id,
            'gig_id' => Gig::factory()->create()->id,
            'file' => pdf(),
        ])
        ->assertSessionHasErrors(['gig_id' => 'Der gewählte Gig gehört nicht zu dieser Band.']);
});

it('lists contracts and riders of the own bands only', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    Document::factory()->for($band)->contract()->create(['title' => 'Unser Vertrag']);
    Document::factory()->for($band)->create(['title' => 'Unser Rider']);
    Document::factory()->create(['title' => 'Fremder Rider']);

    actingAsBandManager($artist)
        ->get(route('band-manager.documents.index'))
        ->assertSuccessful()
        ->assertSee('Unser Vertrag')
        ->assertSee('Unser Rider')
        ->assertDontSee('Fremder Rider')
        ->assertDontSee('Vertrag hinterlegen')
        ->assertSee('Rider hochladen');
});

it('shows and downloads the pdf', function () {
    $document = Document::factory()->create(['original_name' => 'Tech Rider.pdf']);
    Storage::disk('band-manager')->put($document->storagePath(), '%PDF-1.4');

    actingAsBandManager()
        ->get(route('band-manager.documents.show', $document))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertStreamedContent('%PDF-1.4');

    actingAsBandManager()
        ->get(route('band-manager.documents.show', [$document, 'download' => 1]))
        ->assertDownload('Tech Rider.pdf');
});

it('keeps documents of other bands private', function () {
    actingAsBandManager(User::factory()->create())
        ->get(route('band-manager.documents.show', Document::factory()->create()))
        ->assertForbidden();
});

it('lets bands delete riders but not contracts', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();
    $contract = Document::factory()->for($band)->contract()->create();
    $rider = Document::factory()->for($band)->create();
    Storage::disk('band-manager')->put($rider->storagePath(), 'x');

    actingAsBandManager($artist)->delete(route('band-manager.documents.destroy', $contract))->assertForbidden();
    actingAsBandManager($artist)->delete(route('band-manager.documents.destroy', $rider))->assertSessionHas('success', 'Dokument wurde gelöscht.');

    $this->assertModelExists($contract);
    $this->assertModelMissing($rider);
    Storage::disk('band-manager')->assertMissing($rider->storagePath());
});

it('keeps the contract when its gig is deleted', function () {
    $gig = Gig::factory()->create();
    $contract = Document::factory()->for($gig->band)->contract()->create(['gig_id' => $gig->id]);

    actingAsBandManager()->delete(route('band-manager.gigs.destroy', $gig));

    expect($contract->fresh())->not->toBeNull()->gig_id->toBeNull();
});

it('shows linked contracts in the gig form and the latest documents on the dashboard', function () {
    $gig = Gig::factory()->create();
    Document::factory()->for($gig->band)->contract()->create(['gig_id' => $gig->id, 'title' => 'Vertrag Festival']);

    actingAsBandManager()->get(route('band-manager.gigs.edit', $gig))->assertSee('Vertrag Festival')->assertSee('+ Vertrag für diesen Gig hochladen');
    actingAsBandManager()->get(route('band-manager.admin.dashboard'))->assertSee('Neueste Dokumente')->assertSee('Vertrag Festival');
});
