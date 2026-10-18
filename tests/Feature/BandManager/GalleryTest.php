<?php

use App\Enums\BandManager\GalleryCategoryEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('band-manager');
});

it('uploads several photos with previews and sets the first as main', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.gallery.store', $band), [
            'photos' => [UploadedFile::fake()->image('a.jpg', 3200, 2000), UploadedFile::fake()->image('b.png', 500, 500)],
            'category' => 'live',
            'caption' => 'Live in Köln',
            'credit' => 'Max',
            'make_main' => '1',
        ])
        ->assertRedirect(route('band-manager.gallery.band', $band))
        ->assertSessionHas('success', '2 Bilder wurden hochgeladen.');

    $images = $band->galleryImages()->ordered()->get();
    $main = $images->first();

    expect($images)->toHaveCount(2)
        ->and($main->is_main)->toBeTrue()
        ->and($main->category)->toBe(GalleryCategoryEnum::Band)
        ->and($main->getFirstMedia(MediaCollectionEnum::BandPhotos->value)->file_name)->toBe('a.jpg')
        ->and($main->getFirstMedia(MediaCollectionEnum::BandPhotos->value)->getCustomProperty('width'))->toBe(3200)
        ->and($images->last()->category)->toBe(GalleryCategoryEnum::Live)
        ->and(getimagesizefromstring(Storage::disk('band-manager')->get($main->getFirstMedia(MediaCollectionEnum::BandPhotos->value)->getPathRelativeToRoot(MediaConversionEnum::Preview->value)))[0])->toBe(1600)
        ->and(getimagesizefromstring(Storage::disk('band-manager')->get($main->getFirstMedia(MediaCollectionEnum::BandPhotos->value)->getPathRelativeToRoot()))[0])->toBe(3200)
        ->and(ActivityLog::query()->where('action', 'gallery.uploaded')->exists())->toBeTrue();
});

it('rejects photos with too many megapixels before the preview is generated', function () {
    config(['band-manager.gallery.max_megapixels' => 1]);
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.gallery.store', $band), ['photos' => [UploadedFile::fake()->image('riesig.jpg', 2000, 1000)]])
        ->assertSessionHas('error', ['„riesig.jpg“: Das Bild ist zu groß (max. 1 Megapixel).']);

    expect($band->galleryImages()->count())->toBe(0);
});

it('skips invalid files and reports them', function () {
    $band = Band::factory()->create();

    actingAsBandManager()
        ->post(route('band-manager.gallery.store', $band), [
            'photos' => [UploadedFile::fake()->image('ok.jpg'), UploadedFile::fake()->create('rider.pdf', 10, 'application/pdf')],
        ])
        ->assertSessionHas('success', '1 Bild wurde hochgeladen.')
        ->assertSessionHas('error', ['„rider.pdf“: nur JPG-, PNG- oder WebP-Bilder sind erlaubt.'])
        ->assertSessionHas('warning');

    expect($band->galleryImages()->count())->toBe(1);
});

it('limits the number of photos per band', function () {
    config(['band-manager.gallery.max_images' => 2]);
    $band = Band::factory()->create();
    GalleryImage::factory()->for($band)->create();

    actingAsBandManager()
        ->post(route('band-manager.gallery.store', $band), [
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])
        ->assertSessionHas('error', ['Maximal 2 Bilder pro Band – nicht alle ausgewählten Bilder wurden hochgeladen.']);

    expect($band->galleryImages()->count())->toBe(2);

    actingAsBandManager()
        ->post(route('band-manager.gallery.store', $band), ['photos' => [UploadedFile::fake()->image('c.jpg')]])
        ->assertSessionHas('error', 'Die Galerie ist voll (max. 2 Bilder). Bitte zuerst ein Bild löschen.');
});

it('keeps exactly one main image per band', function () {
    $band = Band::factory()->create();
    $oldMain = GalleryImage::factory()->for($band)->main()->create();
    $live = GalleryImage::factory()->for($band)->create(['category' => 'live']);

    actingAsBandManager()->post(route('band-manager.gallery.main', $live))->assertSessionHas('success', 'Hauptbild wurde festgelegt.');

    expect($oldMain->fresh()->is_main)->toBeFalse()
        ->and($live->fresh())->is_main->toBeTrue()->category->toBe(GalleryCategoryEnum::Band);
});

it('updates caption and category but keeps the main image a band photo', function () {
    $main = GalleryImage::factory()->main()->create();

    actingAsBandManager()->put(route('band-manager.gallery.update', $main), ['caption' => 'Neu', 'credit' => '', 'category' => 'live']);

    expect($main->fresh())->caption->toBe('Neu')->credit->toBeNull()->category->toBe(GalleryCategoryEnum::Band);
});

it('deletes an image with its files and warns when it was the main image', function () {
    $image = GalleryImage::factory()->main()->withPhoto()->create();
    expect(Storage::disk('band-manager')->allFiles())->toHaveCount(2);

    actingAsBandManager()
        ->delete(route('band-manager.gallery.destroy', $image))
        ->assertSessionHas('warning', 'Das war das Hauptbild – bitte ein neues Hauptbild festlegen.');

    $this->assertModelMissing($image);
    expect(Storage::disk('band-manager')->allFiles())->toBe([]);
});

it('serves the preview, the original and the download', function () {
    $image = GalleryImage::factory()->withPhoto()->create();
    $media = $image->getFirstMedia(MediaCollectionEnum::BandPhotos->value);
    $disk = Storage::disk('band-manager');
    $preview = $disk->get($media->getPathRelativeToRoot(MediaConversionEnum::Preview->value));
    $original = $disk->get($media->getPathRelativeToRoot());

    expect($preview)->not->toBe($original);

    actingAsBandManager()->get(route('band-manager.gallery.image', $image))->assertSuccessful()->assertHeader('Content-Type', 'image/jpeg')->assertStreamedContent($preview);
    actingAsBandManager()->get(route('band-manager.gallery.image', [$image, 'size' => 'original']))->assertStreamedContent($original);
    actingAsBandManager()->get(route('band-manager.gallery.image', [$image, 'download' => 1]))->assertDownload('bandfoto.jpg');
});

it('keeps galleries of other bands private', function () {
    $artist = User::factory()->create();
    $image = GalleryImage::factory()->create();

    actingAsBandManager($artist)->get(route('band-manager.gallery.band', $image->band))->assertForbidden();
    actingAsBandManager($artist)->get(route('band-manager.gallery.image', $image))->assertForbidden();
    actingAsBandManager($artist)->delete(route('band-manager.gallery.destroy', $image))->assertForbidden();
});

it('shows the gallery overview and the main image in the band profile', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);
    $main = GalleryImage::factory()->for($band)->main()->create();

    actingAsBandManager()->get(route('band-manager.gallery.index'))->assertSuccessful()->assertSee('Iron Kittens')->assertSee('1 / 8 Bilder');
    actingAsBandManager()->get(route('band-manager.gallery.band', $band))->assertSuccessful()->assertSee('Freier Platz');
    actingAsBandManager()->get(route('band-manager.team.band', $band))->assertSee(route('band-manager.gallery.image', $main), false);
});
