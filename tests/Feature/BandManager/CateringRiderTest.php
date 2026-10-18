<?php

use App\Actions\BandManager\Riders\FillCateringRiderPeopleFromTeamAction;
use App\Actions\BandManager\Riders\SummarizeCateringDietsAction;
use App\Actions\MediaLibrary\CreatePdfImageAction;
use App\Enums\BandManager\AllergyEnum;
use App\Enums\BandManager\CateringBuyoutEnum;
use App\Enums\BandManager\CateringMealEnum;
use App\Enums\BandManager\CrewRoleEnum;
use App\Enums\BandManager\UserRoleEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\CateringRider;
use App\Models\BandManager\TeamMember;
use App\Models\BandManager\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * PDF-Vorlage ohne Bandlogo rendern.
 */
function cateringPdfHtml(CateringRider $rider): string
{
    return view('band-manager.riders.catering.pdf', [
        'rider' => $rider,
        'band' => $rider->band,
        'bandLogo' => null,
        'people' => $rider->people ?? [],
        'diets' => app(SummarizeCateringDietsAction::class)->execute($rider->people ?? []),
        'meals' => CateringMealEnum::cases(),
    ])->render();
}

it('shows the catering rider below the tech rider in the rider creator menu', function () {
    actingAsBandManager()
        ->get(route('band-manager.catering-riders.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Rider Creator', 'Tech Rider', 'Catering Rider', 'Export']);
});

it('sends artists with one band straight to their catering rider', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.catering-riders.index'))
        ->assertRedirect(route('band-manager.catering-riders.band', $band));
});

it('prefills the travel party with diets and allergies from band and team', function () {
    $band = Band::factory()->create();
    TeamMember::factory()->for($band)->crew()->create(['first_name' => 'Sam', 'last_name' => 'Sound', 'stage_name' => null, 'crew_role' => CrewRoleEnum::Sound, 'position' => null, 'diet' => '', 'allergy' => AllergyEnum::Other, 'allergy_other' => 'Sellerie']);
    TeamMember::factory()->for($band)->create(['type' => TeamMember::TYPE_MEMBER, 'first_name' => 'Lena', 'last_name' => 'Loud', 'stage_name' => null, 'position' => 'Gesang', 'diet' => 'vegan', 'allergy' => AllergyEnum::Nuts]);

    actingAsBandManager()
        ->get(route('band-manager.catering-riders.band', $band))
        ->assertSuccessful()
        ->assertSeeInOrder(['value="Lena Loud"', 'value="Gesang"', 'value="vegan"', 'value="Nüsse / Erdnüsse"', 'value="Sam Sound"', 'value="Tontechnik"', 'value="Sellerie"'], false)
        ->assertSee('<option value="Gluten">', false)
        ->assertSee('Neu aus „Band/Team“ übernehmen')
        ->assertSee('Buyout gewünscht')
        ->assertSee('name="meals[dinner][wanted]" value="1" id="meal_dinner" checked', false);
});

it('fills empty diets and allergies of a saved rider from band and team but keeps own entries', function () {
    $band = Band::factory()->create();
    TeamMember::factory()->for($band)->create(['first_name' => 'Lena', 'last_name' => 'Loud', 'stage_name' => null, 'diet' => 'vegan', 'allergy' => AllergyEnum::Gluten]);
    TeamMember::factory()->for($band)->create(['first_name' => 'Max', 'last_name' => 'Bass', 'stage_name' => null, 'diet' => 'vegetarisch', 'allergy' => AllergyEnum::Lactose]);
    $rider = CateringRider::factory()->for($band)->create(['people' => [
        ['name' => 'Lena Loud', 'role' => 'Gesang', 'diet' => '', 'allergy' => ''],
        ['name' => 'Max Bass', 'role' => 'Bass', 'diet' => 'pescetarisch', 'allergy' => 'Laktose, Sellerie'],
        ['name' => 'Gast', 'role' => 'Fahrer', 'diet' => '', 'allergy' => ''],
    ]]);

    $people = app(FillCateringRiderPeopleFromTeamAction::class)->execute($band, $rider->people);

    expect($people)->toBe([
        ['name' => 'Lena Loud', 'role' => 'Gesang', 'diet' => 'vegan', 'allergy' => 'Gluten'],
        ['name' => 'Max Bass', 'role' => 'Bass', 'diet' => 'pescetarisch', 'allergy' => 'Laktose, Sellerie'],
        ['name' => 'Gast', 'role' => 'Fahrer', 'diet' => '', 'allergy' => ''],
    ]);

    actingAsBandManager()
        ->get(route('band-manager.catering-riders.band', $band))
        ->assertSee('name="people[0][allergy]" value="Gluten"', false)
        ->assertSee('name="people[1][allergy]" value="Laktose, Sellerie"', false);
});

it('saves the rider with meals, buyout and drinks and drops empty rows', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.catering-riders.update', $band), [
            'contact_name' => 'Tina Tour',
            'people' => [
                ['name' => 'Lena', 'role' => 'Gesang', 'diet' => 'vegan', 'allergy' => 'Nüsse / Erdnüsse, Sellerie'],
                ['name' => '', 'role' => 'Gitarre', 'diet' => '', 'allergy' => ''],
                ['name' => 'Max', 'role' => '', 'diet' => '', 'allergy' => ''],
            ],
            'meals' => [
                'breakfast' => ['wanted' => '1', 'time' => 'bis 10 Uhr', 'wishes' => 'Kaffee'],
                'dinner' => ['wanted' => '1', 'time' => '2 Std. vor Show', 'wishes' => 'Warm'],
                'lunch' => ['wanted' => '0', 'time' => '', 'wishes' => ''],
            ],
            'buyout' => CateringBuyoutEnum::Preferred->value,
            'buyout_amount' => '25',
            'drinks' => [['item' => 'Wasser', 'amount' => '24 Flaschen', 'note' => ''], ['item' => '', 'amount' => '3', 'note' => '']],
            'stage_towels' => '6',
            'needs_shower' => '1',
            'custom_sections' => [['title' => 'Hotel', 'content' => 'Einzelzimmer']],
        ])
        ->assertRedirect(route('band-manager.catering-riders.band', $band))
        ->assertSessionHas('success', 'Catering Rider wurde gespeichert.');

    $rider = $band->cateringRider()->first();

    expect($rider->contact_name)->toBe('Tina Tour')
        ->and($rider->people)->toBe([
            ['name' => 'Lena', 'role' => 'Gesang', 'diet' => 'vegan', 'allergy' => 'Nüsse / Erdnüsse, Sellerie'],
            ['name' => 'Max', 'role' => '', 'diet' => '', 'allergy' => ''],
        ])
        ->and($rider->meals['breakfast'])->toBe(['wanted' => true, 'time' => 'bis 10 Uhr', 'wishes' => 'Kaffee'])
        ->and($rider->meals['lunch']['wanted'])->toBeFalse()
        ->and($rider->meals)->toHaveKeys(['arrival', 'aftershow'])
        ->and($rider->buyout)->toBe(CateringBuyoutEnum::Preferred)
        ->and($rider->buyout_amount)->toBe('25.00')
        ->and($rider->drinks)->toBe([['item' => 'Wasser', 'amount' => '24 Flaschen', 'note' => '']])
        ->and($rider->stage_towels)->toBe(6)
        ->and($rider->needs_shower)->toBeTrue()
        ->and(ActivityLog::query()->where('action', 'catering_rider.saved')->exists())->toBeTrue();
});

it('forgets the buyout amount when catering on site is chosen', function () {
    $rider = CateringRider::factory()->buyout(30)->create();

    actingAsBandManager()
        ->put(route('band-manager.catering-riders.update', $rider->band), ['buyout' => CateringBuyoutEnum::Catering->value, 'buyout_amount' => '30'])
        ->assertSessionHasNoErrors();

    expect($rider->fresh()->buyout_amount)->toBeNull();
});

it('rejects unknown buyout options and invalid values', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.catering-riders.update', $band), [
            'buyout' => 'trinkgeld',
            'buyout_amount' => '-5',
            'contact_email' => 'keine-mail',
            'meals' => ['dinner' => ['wanted' => 'vielleicht']],
        ])
        ->assertSessionHasErrors(['buyout', 'buyout_amount', 'contact_email', 'meals.dinner.wanted']);

    expect(CateringRider::query()->count())->toBe(0);
});

it('summarizes diets and single allergies for the kitchen', function () {
    $summary = app(SummarizeCateringDietsAction::class)->execute([
        ['name' => 'Lena', 'diet' => 'vegan', 'allergy' => 'Nüsse / Erdnüsse, Sellerie'],
        ['name' => 'Max', 'diet' => 'Vegan ', 'allergy' => ''],
        ['name' => 'Sam', 'diet' => '', 'allergy' => 'Sellerie und Gluten'],
        ['name' => 'Tom', 'diet' => '', 'allergy' => ''],
    ]);

    expect($summary)->toBe([
        'diets' => [
            ['label' => 'vegan', 'count' => 2, 'names' => ['Lena', 'Max']],
        ],
        'allergies' => [
            ['label' => 'Sellerie', 'count' => 2, 'names' => ['Lena', 'Sam']],
            ['label' => 'Nüsse / Erdnüsse', 'count' => 1, 'names' => ['Lena']],
            ['label' => 'Gluten', 'count' => 1, 'names' => ['Sam']],
        ],
        'without' => 1,
    ]);
});

it('lists diets, wanted meals and the buyout in the pdf', function () {
    $rider = CateringRider::factory()->buyout(25)->create([
        'people' => [
            ['name' => 'Lena', 'role' => 'Gesang', 'diet' => 'vegan', 'allergy' => 'Gluten'],
            ['name' => 'Max', 'role' => 'Bass', 'diet' => 'vegan', 'allergy' => ''],
            ['name' => 'Tom', 'role' => 'Drums', 'diet' => '', 'allergy' => ''],
        ],
        'meals' => [
            'dinner' => ['wanted' => true, 'time' => '18:00 Uhr', 'wishes' => 'Warmes Essen'],
            'lunch' => ['wanted' => false, 'time' => '', 'wishes' => 'Nicht drucken'],
        ],
    ]);

    $html = cateringPdfHtml($rider);

    expect($html)->toContain('CATERING RIDER')
        ->and($html)->toContain('Allergien – bitte unbedingt beachten')
        ->and($html)->toContain('<span class="diet">Gluten</span>')
        ->and($html)->toContain('<td class="n">2 ×</td>')
        ->and($html)->toContain('Lena, Max')
        ->and($html)->toContain('Ohne Einschränkungen')
        ->and($html)->toContain('Warmes Abendessen')
        ->and($html)->toContain('18:00 Uhr')
        ->and($html)->not->toContain('Nicht drucken')
        ->and($html)->toContain('25,00 €')
        ->and($html)->toContain('bei 3 Personen 75,00 €');
});

it('shows the band logo centered at the top of the pdf', function () {
    Storage::fake('band-manager');
    $band = Band::factory()->create(['name' => 'Iron Kittens']);
    $band->profile->fill(['band_id' => $band->id])->save();
    $band->profile->addMedia(UploadedFile::fake()->image('logo.webp', 1200, 400))->toMediaCollection(MediaCollectionEnum::BandLogos->value);
    $rider = CateringRider::factory()->for($band)->create();

    $html = view('band-manager.riders.catering.pdf', [
        'rider' => $rider,
        'band' => $band,
        'bandLogo' => app(CreatePdfImageAction::class)->execute($band->profile->getFirstMedia(MediaCollectionEnum::BandLogos->value), MediaConversionEnum::PdfLogo->value),
        'people' => $rider->people,
        'diets' => app(SummarizeCateringDietsAction::class)->execute($rider->people),
        'meals' => CateringMealEnum::cases(),
    ])->render();

    expect($html)->toContain('<img src="data:image/png;base64,')
        ->and($html)->toContain('width="230" height="77"')
        ->and($html)->not->toContain('<div class="band">');

    actingAsBandManager()->get(route('band-manager.catering-riders.pdf', $band))->assertSuccessful()->assertDownload('CateringRider_Iron-Kittens.pdf');
});

it('downloads the catering rider as pdf once it is saved', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);

    actingAsBandManager()
        ->get(route('band-manager.catering-riders.pdf', $band))
        ->assertRedirect(route('band-manager.catering-riders.band', $band));

    CateringRider::factory()->for($band)->create(['custom_sections' => [['title' => 'Hotel', 'content' => '**Einzelzimmer**']]]);

    $response = actingAsBandManager()->get(route('band-manager.catering-riders.pdf', $band));

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('CateringRider_Iron-Kittens.pdf');

    expect(substr((string) $response->getContent(), 0, 4))->toBe('%PDF')
        ->and(ActivityLog::query()->where('action', 'catering_rider.pdf')->exists())->toBeTrue();
});

it('shows the catering rider pdf download of each own band on the export page', function () {
    $artist = User::factory()->create();
    $rider = CateringRider::factory()->create();
    $rider->band->users()->attach($artist);
    $emptyBand = Band::factory()->hasAttached($artist)->create();
    $foreignRider = CateringRider::factory()->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.catering-rider-export.index'))
        ->assertSuccessful()
        ->assertSee(route('band-manager.catering-riders.pdf', $rider->band))
        ->assertSee($emptyBand->name)
        ->assertDontSee(route('band-manager.catering-riders.pdf', $emptyBand))
        ->assertSee('Jetzt ausfüllen')
        ->assertDontSee($foreignRider->band->name);
});

it('lists the catering rider below the export heading', function () {
    actingAsBandManager()
        ->get(route('band-manager.exports.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Technischer Rider', 'Catering Rider', 'Verfügbarkeiten (CSV)'])
        ->assertSee(route('band-manager.catering-rider-export.index'));
});

it('keeps catering riders of other bands private', function () {
    $artist = User::factory()->create();
    $rider = CateringRider::factory()->create();

    actingAsBandManager($artist)->get(route('band-manager.catering-riders.band', $rider->band))->assertForbidden();
    actingAsBandManager($artist)->get(route('band-manager.catering-riders.pdf', $rider->band))->assertForbidden();
    actingAsBandManager($artist)->put(route('band-manager.catering-riders.update', $rider->band), ['buyout' => 'catering'])->assertForbidden();
});

it('lets band accesses with view rights read but not edit the catering rider', function () {
    $band = Band::factory()->create();
    $crew = User::factory()->bandAccess(UserRoleEnum::Team, ['riders' => 'view'])->hasAttached($band)->create();

    actingAsBandManager($crew)->get(route('band-manager.catering-riders.band', $band))->assertSuccessful()->assertDontSee('Speichern');
    actingAsBandManager($crew)->put(route('band-manager.catering-riders.update', $band), ['buyout' => 'catering'])->assertForbidden();
});
