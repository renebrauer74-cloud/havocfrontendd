<?php

use App\Enums\BandManager\ContractKindEnum;
use App\Enums\BandManager\RiderCategoryEnum;
use App\Enums\MediaCollectionEnum;
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
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n%Test\n");
}

it('lets the agency upload a gig contract linked to a gig', function () {
    $band = Band::factory()->create();
    $gig = Gig::factory()->for($band)->create();

    actingAsBandManager()
        ->post(route('band-manager.documents.store'), [
            'type' => 'contract',
            'contract_kind' => 'gig',
            'band_id' => $band->id,
            'gig_id' => $gig->id,
            'file' => pdf('Vertrag Stadtfest.pdf'),
            'filter' => $band->id,
        ])
        ->assertRedirect(route('band-manager.documents.index', ['band' => $band->id]))
        ->assertSessionHas('success', 'Gastspielvertrag wurde hinterlegt.');

    $document = Document::sole();

    expect($document)
        ->title->toBe('Vertrag Stadtfest')
        ->contract_kind->toBe(ContractKindEnum::Gig)
        ->gig_id->toBe($gig->id)
        ->category->toBeNull()
        ->and(ActivityLog::query()->where('action', 'document.uploaded')->exists())->toBeTrue();

    expect($document->getFirstMedia(MediaCollectionEnum::Documents->value)->getPathRelativeToRoot())->toStartWith('documents/')->toEndWith('.pdf');
    Storage::disk('band-manager')->assertExists($document->getFirstMedia(MediaCollectionEnum::Documents->value)->getPathRelativeToRoot());
});

it('stores an agency contract without a gig', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.documents.store'), [
            'type' => 'contract',
            'contract_kind' => 'agency',
            'band_id' => $band->id,
            'gig_id' => Gig::factory()->for($band)->create()->id,
            'file' => pdf('Agenturvertrag 2026.pdf'),
        ])
        ->assertSessionHas('success', 'Agenturvertrag wurde hinterlegt.');

    expect(Document::sole())
        ->contract_kind->toBe(ContractKindEnum::Agency)
        ->gig_id->toBeNull()
        ->title->toBe('Agenturvertrag 2026');
});

it('requires the kind of contract and a gig for gig contracts', function (array $data, string $field, string $message) {
    actingAsBandManager()
        ->post(route('band-manager.documents.store'), array_merge(['type' => 'contract', 'band_id' => Band::factory()->create()->id, 'file' => pdf()], $data))
        ->assertSessionHasErrors([$field => $message]);

    expect(Document::count())->toBe(0);
})->with([
    'missing kind' => [[], 'contract_kind', 'Bitte wählen: Agenturvertrag oder Gastspielvertrag.'],
    'unknown kind' => [['contract_kind' => 'invoice'], 'contract_kind', 'Bitte wählen: Agenturvertrag oder Gastspielvertrag.'],
    'gig contract without gig' => [['contract_kind' => 'gig'], 'gig_id', 'Bitte den Gig zum Gastspielvertrag wählen.'],
]);

it('lets bands upload riders for their own band only', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->post(route('band-manager.documents.store'), ['type' => 'rider', 'band_id' => $band->id, 'category' => 'technical', 'title' => 'Tech Rider', 'file' => pdf()])
        ->assertSessionHas('success', 'Rider wurde hochgeladen.');

    actingAsBandManager($artist)
        ->post(route('band-manager.documents.store'), ['type' => 'rider', 'band_id' => Band::factory()->create()->id, 'category' => 'technical', 'file' => pdf()])
        ->assertSessionHasErrors(['band_id' => 'Bitte eine Band wählen.']);

    expect(Document::sole())->type->toBe('rider')->category->toBe(RiderCategoryEnum::Technical)->title->toBe('Tech Rider');
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
            'contract_kind' => 'gig',
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

it('pins the latest agency contract per band and keeps older versions', function () {
    $band = Band::factory()->create(['name' => 'Demo Band']);
    Band::factory()->create(['name' => 'Neue Band']);
    Document::factory()->for($band)->contract()->create(['title' => 'Agenturvertrag 2024', 'created_at' => now()->subYears(2)]);
    Document::factory()->for($band)->contract()->create(['title' => 'Agenturvertrag 2026']);

    actingAsBandManager()
        ->get(route('band-manager.documents.index'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'Agenturverträge', 'Hinterlegt', '1 Band', 'Demo Band', 'Agenturvertrag 2026', 'Frühere Versionen (1)', 'Agenturvertrag 2024',
            'Ohne Agenturvertrag', '1 Band', 'Neue Band', 'Noch kein Agenturvertrag hinterlegt', 'Gastspielverträge',
        ]);
});

it('collapses the agency contracts unless only one band is shown', function () {
    $band = Band::factory()->create();
    Band::factory()->count(2)->create();

    actingAsBandManager()
        ->get(route('band-manager.documents.index'))
        ->assertSee('3 Bands')
        ->assertSee('id="agencyWithout" class="accordion-collapse collapse"', false)
        ->assertDontSee('id="agencyWith"', false);

    actingAsBandManager()
        ->get(route('band-manager.documents.index', ['band' => $band->id]))
        ->assertSee('1 Band')
        ->assertSee('id="agencyWithout" class="accordion-collapse collapse show"', false);
});

it('groups gig contracts by year of the gig', function () {
    $band = Band::factory()->create();
    Document::factory()->forGig(Gig::factory()->for($band)->create(['date' => '2025-06-14']))->create(['title' => 'Vertrag Stadtfest']);
    Document::factory()->forGig(Gig::factory()->for($band)->create(['date' => '2026-08-01']))->create(['title' => 'Vertrag Festival']);
    $deletedGig = Gig::factory()->for($band)->create(['date' => '2024-05-01']);
    Document::factory()->forGig($deletedGig)->create(['title' => 'Vertrag ohne Gig']);
    $deletedGig->delete();

    actingAsBandManager()
        ->get(route('band-manager.documents.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Gastspielverträge', '2026', 'Vertrag Festival', '2025', 'Vertrag Stadtfest', 'Ohne Gig', 'Vertrag ohne Gig', 'Rider'])
        ->assertSee('id="gigContracts0"', false);
});

it('opens the contract form with the gig preselected when coming from a gig', function () {
    $gig = Gig::factory()->create(['date' => now()->subYears(3)->format('Y-m-d')]);

    $response = actingAsBandManager()
        ->get(route('band-manager.documents.index', ['gig' => $gig->id]))
        ->assertSuccessful()
        ->assertSee('class="collapse show" id="contractUpload"', false)
        ->assertSee('<option value="'.$gig->id.'" selected', false);

    expect($response->getContent())->toMatch('/id="contract_kind_gig"[^>]*checked/');
});

it('shows and downloads the pdf', function () {
    $document = Document::factory()->withFile('%PDF-1.4')->create();

    actingAsBandManager()
        ->get(route('band-manager.documents.show', $document))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertStreamedContent('%PDF-1.4');

    actingAsBandManager()
        ->get(route('band-manager.documents.show', [$document, 'download' => 1]))
        ->assertDownload('rider.pdf');
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
    $rider = Document::factory()->for($band)->withFile()->create();
    $riderFile = $rider->getFirstMedia(MediaCollectionEnum::Documents->value)->getPathRelativeToRoot();

    actingAsBandManager($artist)->delete(route('band-manager.documents.destroy', $contract))->assertForbidden();
    actingAsBandManager($artist)->delete(route('band-manager.documents.destroy', $rider))->assertSessionHas('success', 'Dokument wurde gelöscht.');

    $this->assertModelExists($contract);
    $this->assertModelMissing($rider);
    Storage::disk('band-manager')->assertMissing($riderFile);
});

it('keeps the contract when its gig is deleted', function () {
    $gig = Gig::factory()->create();
    $contract = Document::factory()->forGig($gig)->create();

    actingAsBandManager()->delete(route('band-manager.gigs.destroy', $gig));

    expect($contract->fresh())->not->toBeNull()->gig_id->toBeNull()->contract_kind->toBe(ContractKindEnum::Gig);
});

it('shows linked contracts in the gig form and the latest documents on the dashboard', function () {
    $gig = Gig::factory()->create();
    Document::factory()->forGig($gig)->create(['title' => 'Vertrag Festival']);

    actingAsBandManager()->get(route('band-manager.gigs.edit', $gig))->assertSee('Vertrag Festival')->assertSee('+ Vertrag für diesen Gig hochladen');
    actingAsBandManager()->get(route('band-manager.admin.dashboard'))->assertSee('Neueste Dokumente')->assertSee('Vertrag Festival');
});
