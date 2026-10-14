<?php

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

    expect($artist->name)->toBe('Neue Band')
        ->and($artist->genre)->toBe('')
        ->and($artist->image_url)->toStartWith('uploads/artists/');

    Storage::disk('uploads')->assertExists(str($artist->image_url)->after('uploads/'));
});

it('replaces the old image file when a new one is uploaded', function () {
    $artist = Artist::factory()->create();
    $artist->replaceImage(UploadedFile::fake()->image('alt.jpg'));
    $artist->save();
    $oldFile = str($artist->image_url)->after('uploads/')->toString();

    actingAsAdmin()
        ->put(route('admin.artists.update', $artist), [
            'name' => 'Umbenannt',
            'image' => UploadedFile::fake()->image('neu.png'),
        ])
        ->assertRedirect(route('admin.artists.index'));

    $artist->refresh();

    expect($artist->name)->toBe('Umbenannt');
    Storage::disk('uploads')->assertMissing($oldFile);
    Storage::disk('uploads')->assertExists(str($artist->image_url)->after('uploads/'));
});

it('keeps the image when the artist is saved without a new upload', function () {
    $artist = Artist::factory()->create();
    $artist->forceFill(['image_url' => 'uploads/artists/bestehend.jpg'])->save();

    actingAsAdmin()->put(route('admin.artists.update', $artist), ['name' => 'Gleiches Bild']);

    expect($artist->refresh()->image_url)->toBe('uploads/artists/bestehend.jpg');
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
    TourDate::factory()->for($artist)->create();
    $photo = GalleryImage::factory()->for($artist)->make();
    $photo->replaceImage(UploadedFile::fake()->image('live.jpg'));
    $photo->save();
    $photoFile = str($photo->image_url)->after('uploads/')->toString();

    actingAsAdmin()
        ->delete(route('admin.artists.destroy', $artist))
        ->assertRedirect(route('admin.artists.index'));

    $this->assertModelMissing($artist);
    expect(TourDate::count())->toBe(0)
        ->and(GalleryImage::count())->toBe(0);
    Storage::disk('uploads')->assertMissing($photoFile);
});
