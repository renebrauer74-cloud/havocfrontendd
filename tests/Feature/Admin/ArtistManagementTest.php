<?php

use App\Enums\MediaCollectionEnum;
use App\Models\Artist;
use App\Models\GalleryImage;
use App\Models\TourDate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
});

it('renders the artist form and list', function () {
    $artist = Artist::factory()->create(['name' => 'Bekannte Band']);

    actingAsAdmin()->get(route('admin.artists.index'))->assertOk()->assertSee('Bekannte Band');
    actingAsAdmin()->get(route('admin.artists.edit', $artist))->assertOk()->assertSee('Bearbeitungs-Modus');
});

it('creates an artist with an uploaded image', function () {
    actingAsAdmin()
        ->post(route('admin.artists.store'), [
            'name' => 'Neue Band',
            'genre' => '',
            'image' => UploadedFile::fake()->image('band.jpg'),
        ])
        ->assertRedirect(route('admin.artists.index'))
        ->assertSessionHas('status', 'Künstlerprofil gespeichert //');

    $artist = Artist::sole();
    $image = $artist->getFirstMedia(MediaCollectionEnum::Artists->value);

    expect($artist->name)->toBe('Neue Band')
        ->and($artist->genre)->toBe('')
        ->and($image->disk)->toBe('uploads')
        ->and($image->getPathRelativeToRoot())->toStartWith('artists/'.$image->uuid.'/')
        ->and($artist->getFirstMediaUrl(MediaCollectionEnum::Artists->value))->toBe($image->getUrl());

    Storage::disk('uploads')->assertExists($image->getPathRelativeToRoot());
});

it('replaces the old image file when a new one is uploaded', function () {
    $artist = Artist::factory()->create();
    $artist->addMedia(UploadedFile::fake()->image('alt.jpg'))->toMediaCollection(MediaCollectionEnum::Artists->value);
    $oldFile = $artist->getFirstMedia(MediaCollectionEnum::Artists->value)->getPathRelativeToRoot();

    actingAsAdmin()
        ->put(route('admin.artists.update', $artist), [
            'name' => 'Umbenannt',
            'image' => UploadedFile::fake()->image('neu.png'),
        ])
        ->assertRedirect(route('admin.artists.index'));

    $artist->refresh();

    expect($artist->name)->toBe('Umbenannt')
        ->and($artist->getMedia(MediaCollectionEnum::Artists->value))->toHaveCount(1);
    Storage::disk('uploads')->assertMissing($oldFile);
    Storage::disk('uploads')->assertExists($artist->getFirstMedia(MediaCollectionEnum::Artists->value)->getPathRelativeToRoot());
});

it('keeps the image when the artist is saved without a new upload', function () {
    $artist = Artist::factory()->create();
    $artist->addMedia(UploadedFile::fake()->image('bestehend.jpg'))->toMediaCollection(MediaCollectionEnum::Artists->value);
    $uuid = $artist->getFirstMedia(MediaCollectionEnum::Artists->value)->uuid;

    actingAsAdmin()->put(route('admin.artists.update', $artist), ['name' => 'Gleiches Bild']);

    expect($artist->refresh()->getFirstMedia(MediaCollectionEnum::Artists->value)->uuid)->toBe($uuid);
});

it('rejects uploads that are not images', function () {
    actingAsAdmin()
        ->post(route('admin.artists.store'), [
            'name' => 'Band',
            'image' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
        ])
        ->assertSessionHasErrors('image');

    expect(Artist::count())->toBe(0);
});

it('requires a name', function () {
    actingAsAdmin()
        ->post(route('admin.artists.store'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'Bitte Name ausfüllen.']);
});

it('deletes an artist with tour dates, gallery and image files', function () {
    $artist = Artist::factory()->create();
    $artist->addMedia(UploadedFile::fake()->image('band.jpg'))->toMediaCollection(MediaCollectionEnum::Artists->value);
    TourDate::factory()->for($artist)->create();
    GalleryImage::factory()->for($artist)->withImage()->create();

    actingAsAdmin()
        ->delete(route('admin.artists.destroy', $artist))
        ->assertRedirect(route('admin.artists.index'));

    $this->assertModelMissing($artist);
    expect(TourDate::count())->toBe(0)
        ->and(GalleryImage::count())->toBe(0)
        ->and(Storage::disk('uploads')->allFiles())->toBe([]);
});
