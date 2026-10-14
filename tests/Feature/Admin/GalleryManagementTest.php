<?php

use App\Models\Artist;
use App\Models\GalleryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
});

it('shows the gallery of the selected artist', function () {
    $artist = Artist::factory()->create();
    GalleryImage::factory()->for($artist)->create(['image_url' => 'uploads/gallery/foto.jpg']);

    actingAsAdmin()->get(route('admin.gallery.index'))->assertOk()->assertSee('Bitte wähle zuerst eine Band aus.');
    actingAsAdmin()->get(route('admin.gallery.index', ['artist' => $artist->id]))->assertOk()->assertSee('uploads/gallery/foto.jpg');
});

it('uploads a gallery image', function () {
    $artist = Artist::factory()->create();

    actingAsAdmin()
        ->post(route('admin.gallery.store'), [
            'artist_id' => $artist->id,
            'image' => UploadedFile::fake()->image('live.webp'),
        ])
        ->assertRedirect(route('admin.gallery.index', ['artist' => $artist->id]));

    expect($artist->galleryImages()->sole()->image_url)->toStartWith('uploads/gallery/');
});

it('requires an image', function () {
    $artist = Artist::factory()->create();

    actingAsAdmin()
        ->post(route('admin.gallery.store'), ['artist_id' => $artist->id])
        ->assertSessionHasErrors(['image' => 'Bitte ein Bild auswählen.']);
});

it('allows at most eight images per artist', function () {
    $artist = Artist::factory()->create();
    GalleryImage::factory(GalleryImage::MAX_PER_ARTIST)->for($artist)->create();

    actingAsAdmin()
        ->post(route('admin.gallery.store'), [
            'artist_id' => $artist->id,
            'image' => UploadedFile::fake()->image('zu-viel.jpg'),
        ])
        ->assertSessionHasErrors(['image' => 'Maximal 8 Galeriebilder pro Band.']);

    expect($artist->galleryImages()->count())->toBe(GalleryImage::MAX_PER_ARTIST);
});

it('deletes a gallery image and its file', function () {
    $photo = GalleryImage::factory()->make(['artist_id' => Artist::factory()->create()->id]);
    $photo->replaceImage(UploadedFile::fake()->image('weg.jpg'));
    $photo->save();
    $file = str($photo->image_url)->after('uploads/')->toString();

    actingAsAdmin()
        ->delete(route('admin.gallery.destroy', $photo))
        ->assertRedirect(route('admin.gallery.index', ['artist' => $photo->artist_id]));

    $this->assertModelMissing($photo);
    Storage::disk('uploads')->assertMissing($file);
});
