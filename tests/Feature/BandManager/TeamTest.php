<?php

use App\Enums\BandManager\AllergyEnum;
use App\Enums\BandManager\CrewRoleEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\BandProfile;
use App\Models\BandManager\Document;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;
use App\Models\BandManager\User;
use App\Services\BandManager\DaySheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('band-manager');
});

it('shows the band with members, crew and profile', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);
    TeamMember::factory()->for($band)->create(['first_name' => 'Kim', 'last_name' => 'Kitten', 'position' => 'Gesang']);
    TeamMember::factory()->for($band)->crew('other')->create(['first_name' => 'Sam', 'crew_role_other' => 'Fotograf']);
    BandProfile::query()->create(['band_id' => $band->id, 'bio_de' => 'Laute **Band**', 'links' => ['instagram' => 'https://www.instagram.com/kittens/']]);

    actingAsBandManager()
        ->get(route('band-manager.team.band', $band))
        ->assertSuccessful()
        ->assertSee('Iron Kittens – Band &amp; Team', false)
        ->assertSee('Kim Kitten')
        ->assertSee('Gesang')
        ->assertSee('Fotograf')
        ->assertSee('Laute <strong>Band</strong>', false)
        ->assertSee('Instagram')
        ->assertSee('Noch kein Hauptbild');
});

it('sends a band with exactly one band straight to its team page', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)->get(route('band-manager.team.index'))->assertRedirect(route('band-manager.team.band', $band));
});

it('keeps other bands private', function () {
    $artist = User::factory()->create();
    $person = TeamMember::factory()->create();

    actingAsBandManager($artist)->get(route('band-manager.team.band', $person->band))->assertForbidden();
    actingAsBandManager($artist)->get(route('band-manager.team.show', $person))->assertForbidden();
    actingAsBandManager($artist)->post(route('band-manager.team.store', $person->band), ['first_name' => 'X'])->assertForbidden();
});

it('creates a band member with a resized photo', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.team.store', $band), [
            'type' => 'member',
            'first_name' => 'Kim',
            'email' => 'Kim@Example.com',
            'birth_date' => '1990-05-01',
            'photo' => UploadedFile::fake()->image('kim.png', 2000, 1000),
        ])
        ->assertSessionHas('success', 'Bandmitglied wurde angelegt.');

    $person = TeamMember::sole();

    expect($person)
        ->email->toBe('kim@example.com')
        ->type->toBe('member')
        ->crew_role->toBeNull()
        ->and($person->getFirstMedia(MediaCollectionEnum::MemberPhotos->value)->file_name)->toBe('kim.png')
        ->and(getimagesizefromstring(Storage::disk('band-manager')->get($person->getFirstMedia(MediaCollectionEnum::MemberPhotos->value)->getPathRelativeToRoot(MediaConversionEnum::Preview->value)))[0])->toBe(800)
        ->and(ActivityLog::query()->where('action', 'team.created')->exists())->toBeTrue();

    $this->get(route('band-manager.team.photo', $person))->assertSuccessful();
});

it('validates team members', function (array $data, string $field) {
    actingAsBandManager()
        ->post(route('band-manager.team.store', Band::factory()->create()), array_merge(['type' => 'crew', 'first_name' => 'Sam', 'crew_role' => 'sound'], $data))
        ->assertSessionHasErrors($field);
})->with([
    'missing first name' => [['first_name' => ''], 'first_name'],
    'missing crew role' => [['crew_role' => ''], 'crew_role'],
    'other role without text' => [['crew_role' => 'other'], 'crew_role_other'],
    'invalid email' => [['email' => 'nope'], 'email'],
    'birth date in the future' => [['birth_date' => '2999-01-01'], 'birth_date'],
    'unknown shirt size' => [['shirt_size' => 'XXXXL'], 'shirt_size'],
    'unknown allergy' => [['allergies' => ['gluten', 'pollen']], 'allergies.1'],
    'other allergy without text' => [['allergies' => ['nuts', 'other']], 'allergy_other'],
    'not an image' => [['photo' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')], 'photo'],
]);

it('saves several allergies from the list plus own text and shows them in the profile', function () {
    $person = TeamMember::factory()->create(['first_name' => 'Kim']);

    actingAsBandManager()->get(route('band-manager.team.edit', $person))
        ->assertSee('name="allergies[]" value="nuts"', false)
        ->assertSee('Welche Allergie?');

    actingAsBandManager()->put(route('band-manager.team.update', $person), ['first_name' => 'Kim', 'allergies' => ['gluten', 'lactose', 'other'], 'allergy_other' => 'Sellerie'])
        ->assertSessionHasNoErrors();

    expect($person->fresh())
        ->allergies->all()->toBe([AllergyEnum::Gluten, AllergyEnum::Lactose, AllergyEnum::Other])
        ->allergy_other->toBe('Sellerie');

    actingAsBandManager()->get(route('band-manager.team.show', $person))->assertSeeInOrder(['Allergien', 'Gluten, Laktose, Sellerie']);

    actingAsBandManager()->put(route('band-manager.team.update', $person), ['first_name' => 'Kim', 'allergies' => ['nuts'], 'allergy_other' => 'Sellerie']);

    expect($person->fresh())->allergies->all()->toBe([AllergyEnum::Nuts])->allergy_other->toBeNull();

    actingAsBandManager()->put(route('band-manager.team.update', $person), ['first_name' => 'Kim']);

    expect($person->fresh())->allergies->all()->toBe([]);
});

it('keeps the custom crew role only for "other"', function () {
    $person = TeamMember::factory()->crew('other')->create(['crew_role_other' => 'Fotograf']);

    actingAsBandManager()->put(route('band-manager.team.update', $person), [
        'first_name' => 'Sam',
        'crew_role' => 'light',
        'crew_role_other' => 'Fotograf',
    ]);

    expect($person->fresh())
        ->crew_role->toBe(CrewRoleEnum::Light)
        ->crew_role_other->toBeNull()
        ->type->toBe('crew');
});

it('replaces and removes photos', function () {
    $person = TeamMember::factory()->create();

    actingAsBandManager()->put(route('band-manager.team.update', $person), ['first_name' => 'Kim', 'photo' => UploadedFile::fake()->image('a.jpg')]);
    $firstPhoto = $person->fresh()->getFirstMedia(MediaCollectionEnum::MemberPhotos->value)->getPathRelativeToRoot();

    actingAsBandManager()->put(route('band-manager.team.update', $person), ['first_name' => 'Kim', 'photo' => UploadedFile::fake()->image('b.jpg')]);
    Storage::disk('band-manager')->assertMissing($firstPhoto);

    actingAsBandManager()->put(route('band-manager.team.update', $person), ['first_name' => 'Kim', 'remove_photo' => '1']);

    expect($person->fresh()->hasMedia(MediaCollectionEnum::MemberPhotos->value))->toBeFalse()
        ->and(Storage::disk('band-manager')->allFiles())->toBe([]);
});

it('deletes a person including the photo', function () {
    $person = TeamMember::factory()->create();
    $person->addMedia(UploadedFile::fake()->image('abc.jpg'))->toMediaCollection(MediaCollectionEnum::MemberPhotos->value);
    $photo = $person->getFirstMedia(MediaCollectionEnum::MemberPhotos->value)->getPathRelativeToRoot();

    actingAsBandManager()
        ->delete(route('band-manager.team.destroy', $person))
        ->assertRedirect(route('band-manager.team.band', $person->band_id));

    $this->assertModelMissing($person);
    Storage::disk('band-manager')->assertMissing($photo);
});

it('requires the edit permission for changes', function () {
    config(['band-manager.permissions.artist' => ['team.view']]);
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)->get(route('band-manager.team.band', $band))->assertSuccessful()->assertDontSee('Bandmitglied</a>', false);
    actingAsBandManager($artist)->get(route('band-manager.team.create', $band))->assertForbidden();
});

it('saves the band profile with normalized links and a logo', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.team.profile.update', $band), [
            'bio_de' => 'Deutsch',
            'bio_en' => 'English',
            'link_instagram' => '@ironkittens',
            'link_website' => 'ironkittens.de',
            'link_spotify' => '',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 200),
        ])
        ->assertRedirect(route('band-manager.team.band', $band));

    $profile = $band->profile()->sole();

    expect($profile->links)->toBe([
        'website' => 'https://ironkittens.de',
        'instagram' => 'https://www.instagram.com/ironkittens/',
    ])->and($profile->getFirstMedia(MediaCollectionEnum::BandLogos->value)->file_name)->toBe('Logo_'.str_replace(' ', '-', $band->name).'.png');

    $this->get(route('band-manager.team.logo', $band))->assertSuccessful();
    $this->get(route('band-manager.team.logo', [$band, 'download' => 1]))->assertDownload('Logo_'.str_replace(' ', '-', $band->name).'.png');
});

it('rejects invalid profile links', function () {
    actingAsBandManager()
        ->put(route('band-manager.team.profile.update', Band::factory()->create()), ['link_spotify' => '@nur-handle'])
        ->assertSessionHasErrors(['link_spotify' => 'Spotify: Bitte einen gültigen Link eingeben (z. B. https://…).']);
});

it('removes the logo', function () {
    $band = Band::factory()->create();
    $profile = BandProfile::query()->create(['band_id' => $band->id]);
    $profile->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection(MediaCollectionEnum::BandLogos->value);
    $logo = $profile->getFirstMedia(MediaCollectionEnum::BandLogos->value)->getPathRelativeToRoot();

    actingAsBandManager()->put(route('band-manager.team.profile.update', $band), ['remove_logo' => '1']);

    expect($band->profile()->sole()->hasMedia(MediaCollectionEnum::BandLogos->value))->toBeFalse();
    Storage::disk('band-manager')->assertMissing($logo);
});

it('deletes all band files when a band is deleted', function () {
    $band = Band::factory()->create();
    TeamMember::factory()->for($band)->create()->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection(MediaCollectionEnum::MemberPhotos->value);
    GalleryImage::factory()->for($band)->withPhoto()->create();
    Document::factory()->for($band)->withFile()->create();
    BandProfile::query()->create(['band_id' => $band->id])->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection(MediaCollectionEnum::BandLogos->value);
    // 4 Originale + Vorschau (Person, Galerie) + PDF-Logo
    expect(Media::count())->toBe(4)
        ->and(Storage::disk('band-manager')->allFiles())->toHaveCount(7);

    actingAsBandManager()->delete(route('band-manager.admin.bands.destroy', $band));

    expect(Storage::disk('band-manager')->allFiles())->toBe([])
        ->and(Media::count())->toBe(0);
});

it('lists the selected travel party on the day sheet', function () {
    $band = Band::factory()->create();
    $sam = TeamMember::factory()->for($band)->crew()->create(['first_name' => 'Sam', 'last_name' => 'Sound', 'diet' => 'vegan', 'allergies' => [AllergyEnum::Gluten, AllergyEnum::Lactose]]);
    $kim = TeamMember::factory()->for($band)->create(['first_name' => 'Kim', 'last_name' => 'Kitten', 'stage_name' => 'Kitty']);
    TeamMember::factory()->for($band)->create(['first_name' => 'Daheim', 'last_name' => 'Bleiber']);
    $gig = Gig::factory()->for($band)->create();
    $gig->travelers()->attach([$sam->id, $kim->id]);

    $html = view('band-manager.gigs.pdf', [
        'gig' => $gig,
        'band' => $band,
        'logo' => null,
        'team' => app(DaySheet::class)->travelParty($gig),
        'catering' => [],
    ])->render();

    expect($html)->toContain('Reisegruppe (2 Personen)')
        ->toContain('Kim Kitten („Kitty“)')
        ->toContain('vegan · Allergie: Gluten, Laktose')
        ->not->toContain('Daheim Bleiber')
        ->and(strpos($html, 'Kim Kitten'))->toBeLessThan(strpos($html, 'Sam Sound'));
});

it('leaves out the travel party when nobody is selected', function () {
    $band = Band::factory()->create();
    TeamMember::factory()->for($band)->create();
    $gig = Gig::factory()->for($band)->create();

    expect(app(DaySheet::class)->travelParty($gig))->toBe([]);
});
