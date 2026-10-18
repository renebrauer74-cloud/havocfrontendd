<?php

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Document;
use App\Models\TeamMember;
use Illuminate\Foundation\CloudBootstrapper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Hängt einen Bucket so an, wie es Laravel Cloud über LARAVEL_CLOUD_DISK_CONFIG tut.
 */
function attachCloudBucket(string $diskName, string $publicUrl): void
{
    $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode([[
        'disk' => $diskName,
        'access_key_id' => 'test-key',
        'access_key_secret' => 'test-secret',
        'bucket' => 'havoc-uploads',
        'url' => $publicUrl,
        'endpoint' => 'https://example.r2.cloudflarestorage.com',
        'is_default' => false,
    ]]);

    CloudBootstrapper::configureDisks(app());
    Storage::forgetDisk($diskName);
}

afterEach(function () {
    unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);
});

it('stores website images in a subfolder per collection with an unguessable path', function () {
    Storage::fake('uploads');
    $member = TeamMember::factory()->create();
    $member->addMedia(UploadedFile::fake()->image('profil.jpg'))->toMediaCollection(MediaCollectionEnum::Team->value);

    $media = $member->getFirstMedia(MediaCollectionEnum::Team->value);

    expect($media->disk)->toBe('uploads')
        ->and($media->getPathRelativeToRoot())->toBe("team/{$media->uuid}/profil.jpg");
    Storage::disk('uploads')->assertExists($media->getPathRelativeToRoot());
});

it('serves website images from the bucket attached as uploads disk', function () {
    Storage::fake('uploads');
    $member = TeamMember::factory()->create(['name' => 'Rene']);
    $member->addMedia(UploadedFile::fake()->image('profil.jpg'))->toMediaCollection(MediaCollectionEnum::Team->value);
    $uuid = $member->getFirstMedia(MediaCollectionEnum::Team->value)->uuid;

    attachCloudBucket('uploads', 'https://havoc-uploads.example.r2.dev');

    $this->get(route('team'))
        ->assertOk()
        ->assertSee("https://havoc-uploads.example.r2.dev/team/{$uuid}/profil.jpg");
});

it('keeps band manager files in their own subfolder of the uploads disk', function () {
    expect(config('filesystems.disks.band-manager'))->toMatchArray(['driver' => 'scoped', 'disk' => 'uploads', 'prefix' => 'band-manager'])
        ->and(config('havoc.uploads.band_manager_disk'))->toBe('band-manager');
});

it('moves media to another disk and keeps serving it', function () {
    Storage::fake('band-manager');
    config(['filesystems.disks.band-manager-private' => ['driver' => 'local', 'root' => storage_path('framework/testing/disks/band-manager-private')]]);
    Storage::fake('band-manager-private');

    $document = Document::factory()->withFile('%PDF-1.4 Vertrag')->create();
    $path = $document->getFirstMedia(MediaCollectionEnum::Documents->value)->getPathRelativeToRoot();

    $this->artisan('havoc:move-media', ['from' => 'band-manager', 'to' => 'band-manager-private'])
        ->expectsOutputToContain('1 Datei(en)')
        ->assertSuccessful();

    expect($document->fresh()->getFirstMedia(MediaCollectionEnum::Documents->value)->disk)->toBe('band-manager-private');
    Storage::disk('band-manager')->assertMissing($path);
    expect(Storage::disk('band-manager-private')->get($path))->toBe('%PDF-1.4 Vertrag');

    actingAsBandManager()->get(route('band-manager.documents.show', $document))->assertSuccessful()->assertStreamedContent('%PDF-1.4 Vertrag');
});

it('refuses to move media to an unknown disk', function () {
    $this->artisan('havoc:move-media', ['from' => 'band-manager', 'to' => 'gibt-es-nicht'])->assertFailed();
});
