<?php

use App\Actions\BandManager\Cloud\CalculateCloudUsageAction;
use App\Actions\BandManager\Cloud\TrashCloudFolderAction;
use App\Enums\BandManager\UserRoleEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('band-manager');
});

/**
 * Band-Login (Rolle "artist"), der der Band zugeordnet ist.
 */
function cloudBandUser(Band $band): User
{
    $user = User::factory()->create();
    $band->users()->attach($user);

    return $user;
}

function cloudMedia(CloudFile $file): Media
{
    return $file->refresh()->getFirstMedia(MediaCollectionEnum::CloudFiles->value);
}

it('creates the default folders on the first visit and keeps the main folders locked', function () {
    $band = Band::factory()->create();

    actingAsBandManager(cloudBandUser($band))
        ->get(route('band-manager.cloud.band', $band))
        ->assertOk()
        ->assertSee('Fotos')
        ->assertSee('Recording-Projekte');

    $recording = $band->cloudFolders()->whereNull('parent_id')->where('name', 'Recording-Projekte')->first();

    expect($band->cloudFolders()->whereNull('parent_id')->count())->toBe(count(config('band-manager.cloud.default_folders')))
        ->and($recording->isLocked())->toBeTrue()
        ->and($recording->children()->pluck('name')->all())->toContain('Maps & Presets');

    actingAsBandManager(cloudBandUser($band))
        ->get(route('band-manager.cloud.band', $band));

    expect($band->cloudFolders()->whereNull('parent_id')->count())->toBe(count(config('band-manager.cloud.default_folders')));

    actingAsBandManager(cloudBandUser($band))->put(route('band-manager.cloud.folders.update', $recording), ['name' => 'Anders'])->assertForbidden();
    actingAsBandManager(cloudBandUser($band))->delete(route('band-manager.cloud.folders.destroy', $recording))->assertForbidden();
});

it('uploads a file into a folder of the band', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create(['name' => 'Tour 2026']);

    actingAsBandManager(cloudBandUser($band))
        ->postJson(route('band-manager.cloud.files.store', $band), [
            'file' => UploadedFile::fake()->image('live.jpg', 1200, 800),
            'folder_id' => $folder->id,
        ])
        ->assertCreated()
        ->assertJson(['name' => 'live.jpg']);

    $file = $folder->files()->sole();
    $media = cloudMedia($file);

    expect($media->getPathRelativeToRoot())->toBe("cloud/{$band->id}/{$media->uuid}/live.jpg")
        ->and($media->hasGeneratedConversion(MediaConversionEnum::Thumbnail->value))->toBeTrue()
        ->and(ActivityLog::query()->where('action', 'cloud.uploaded')->exists())->toBeTrue();

    Storage::disk('band-manager')->assertExists($media->getPathRelativeToRoot());
});

it('rejects executable files', function (string $name) {
    $band = Band::factory()->create();

    actingAsBandManager(cloudBandUser($band))
        ->postJson(route('band-manager.cloud.files.store', $band), ['file' => UploadedFile::fake()->create($name, 5)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    expect($band->cloudFiles()->count())->toBe(0);
})->with(['setup.exe', 'script.PS1', 'tool.exe.zip', 'shell.php']);

it('refuses uploads beyond the storage quota', function () {
    config(['band-manager.cloud.quota_gb' => 50 / 1024 / 1024]);   // 50 KB
    $band = Band::factory()->create();
    CloudFile::factory()->for($band)->withFile('alt.wav', 40)->create();

    actingAsBandManager(cloudBandUser($band))
        ->postJson(route('band-manager.cloud.files.store', $band), ['file' => UploadedFile::fake()->create('neu.wav', 20)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file' => 'Nicht genug Speicherplatz']);

    expect($band->cloudFiles()->count())->toBe(1);
});

it('counts files in the trash towards the quota', function () {
    $band = Band::factory()->create();
    CloudFile::factory()->for($band)->withFile('a.wav', 30)->create();
    CloudFile::factory()->for($band)->withFile('b.wav', 20)->create()->delete();

    $usage = app(CalculateCloudUsageAction::class)->execute($band);

    expect($usage['used'])->toBe(50 * 1024)
        ->and($usage['trashed'])->toBe(20 * 1024)
        ->and($usage['quota'])->toBe(10 * 1024 ** 3);
});

it('keeps bands out of other bands clouds', function () {
    $band = Band::factory()->create();
    $other = Band::factory()->create();
    $file = CloudFile::factory()->for($other)->withFile()->create();
    $user = cloudBandUser($band);

    actingAsBandManager($user)->get(route('band-manager.cloud.band', $other))->assertForbidden();
    actingAsBandManager($user)->get(route('band-manager.cloud.files.show', $file))->assertForbidden();
    actingAsBandManager($user)->postJson(route('band-manager.cloud.files.store', $other), ['file' => UploadedFile::fake()->create('x.txt', 1)])->assertForbidden();
    actingAsBandManager($user)->delete(route('band-manager.cloud.files.destroy', $file))->assertForbidden();
});

it('follows the released cloud area of band accesses', function () {
    $band = Band::factory()->create();
    $viewer = User::factory()->bandAccess(UserRoleEnum::Team, ['cloud' => 'view'])->create();
    $without = User::factory()->bandAccess(UserRoleEnum::Photographer, ['gallery' => 'edit'])->create();
    $band->users()->attach([$viewer->id, $without->id]);

    actingAsBandManager($viewer)->get(route('band-manager.cloud.band', $band))->assertOk()->assertDontSee('Dateien hochladen');
    actingAsBandManager($viewer)->postJson(route('band-manager.cloud.files.store', $band), ['file' => UploadedFile::fake()->create('x.txt', 1)])->assertForbidden();
    actingAsBandManager($without)->get(route('band-manager.cloud.band', $band))->assertForbidden();
});

it('creates folders with unique names per location', function () {
    $band = Band::factory()->create();
    $parent = CloudFolder::factory()->for($band)->create(['name' => 'Tour 2026']);
    $user = cloudBandUser($band);

    actingAsBandManager($user)
        ->post(route('band-manager.cloud.folders.store', $band), ['name' => ' Köln ', 'parent_id' => $parent->id])
        ->assertRedirect(route('band-manager.cloud.folder', $parent))
        ->assertSessionHas('success');

    actingAsBandManager($user)
        ->post(route('band-manager.cloud.folders.store', $band), ['name' => 'Köln', 'parent_id' => $parent->id])
        ->assertSessionHasErrors(['name' => 'Hier gibt es schon einen Ordner „Köln“.']);

    actingAsBandManager($user)
        ->post(route('band-manager.cloud.folders.store', $band), ['name' => 'a/b'])
        ->assertSessionHasErrors('name');

    expect($parent->children()->pluck('name')->all())->toBe(['Köln']);
});

it('moves folders but never into themselves', function () {
    $band = Band::factory()->create();
    $tour = CloudFolder::factory()->for($band)->create(['name' => 'Tour']);
    $cologne = CloudFolder::factory()->inFolder($tour)->create(['name' => 'Köln']);
    $archive = CloudFolder::factory()->for($band)->create(['name' => 'Archiv']);
    $user = cloudBandUser($band);

    actingAsBandManager($user)
        ->put(route('band-manager.cloud.folders.update', $tour), ['name' => 'Tour', 'parent_id' => $cologne->id])
        ->assertSessionHasErrors(['parent_id' => 'Ein Ordner kann nicht in sich selbst oder einen seiner Unterordner verschoben werden.']);

    actingAsBandManager($user)
        ->put(route('band-manager.cloud.folders.update', $tour), ['name' => 'Tour 2026', 'parent_id' => $archive->id])
        ->assertRedirect(route('band-manager.cloud.band', $band));

    expect($tour->refresh()->parent_id)->toBe($archive->id)
        ->and($tour->name)->toBe('Tour 2026');
});

it('renames a file but keeps its extension and moves it in the bucket', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $file = CloudFile::factory()->for($band)->withFile('mix.wav')->create();
    $oldPath = cloudMedia($file)->getPathRelativeToRoot();

    actingAsBandManager(cloudBandUser($band))
        ->put(route('band-manager.cloud.files.update', $file), ['name' => 'Final Mix', 'folder_id' => $folder->id])
        ->assertRedirect(route('band-manager.cloud.band', $band));

    $media = cloudMedia($file);

    expect($media->file_name)->toBe('Final-Mix.wav')
        ->and($file->folder_id)->toBe($folder->id);

    Storage::disk('band-manager')->assertMissing($oldPath);
    Storage::disk('band-manager')->assertExists($media->getPathRelativeToRoot());
});

it('does not allow renaming a file into an executable', function () {
    $band = Band::factory()->create();
    $file = CloudFile::factory()->for($band)->withFile('readme')->create();

    actingAsBandManager(cloudBandUser($band))
        ->put(route('band-manager.cloud.files.update', $file), ['name' => 'readme.exe'])
        ->assertSessionHasErrors('name');
});

it('only shows safe formats in the browser', function () {
    $band = Band::factory()->create();
    $user = cloudBandUser($band);
    $html = CloudFile::factory()->for($band)->create();
    $html->addMedia(UploadedFile::fake()->createWithContent('seite.html', '<html><script>alert(1)</script></html>'))
        ->withCustomProperties(['band_id' => $band->id])
        ->toMediaCollection(MediaCollectionEnum::CloudFiles->value);
    $image = CloudFile::factory()->for($band)->create();
    $image->addMedia(UploadedFile::fake()->image('foto.jpg'))
        ->withCustomProperties(['band_id' => $band->id])
        ->toMediaCollection(MediaCollectionEnum::CloudFiles->value);

    expect(actingAsBandManager($user)->get(route('band-manager.cloud.files.show', $html))->headers->get('Content-Disposition'))->toStartWith('attachment')
        ->and(actingAsBandManager($user)->get(route('band-manager.cloud.files.show', $image))->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and(actingAsBandManager($user)->get(route('band-manager.cloud.files.show', [$image, 'download' => 1]))->headers->get('Content-Disposition'))->toStartWith('attachment');
});

it('moves a folder with its content to the trash and restores it together', function () {
    $band = Band::factory()->create();
    $tour = CloudFolder::factory()->for($band)->create(['name' => 'Tour']);
    $cologne = CloudFolder::factory()->inFolder($tour)->create(['name' => 'Köln']);
    $photo = CloudFile::factory()->inFolder($cologne)->withFile('foto.jpg')->create();
    $earlier = CloudFile::factory()->inFolder($tour)->withFile('alt.txt')->create();
    $user = cloudBandUser($band);

    $earlier->delete();
    $this->travel(1)->minute();

    actingAsBandManager($user)->delete(route('band-manager.cloud.folders.destroy', $tour))->assertRedirect(route('band-manager.cloud.band', $band));

    expect(CloudFolder::query()->whereKey([$tour->id, $cologne->id])->count())->toBe(0)
        ->and(CloudFile::query()->whereKey($photo->id)->exists())->toBeFalse();

    actingAsBandManager($user)
        ->get(route('band-manager.cloud.trash', $band))
        ->assertOk()
        ->assertSee('Tour')
        ->assertSee('alt.txt')
        ->assertDontSee('Köln');

    actingAsBandManager($user)->post(route('band-manager.cloud.trash.folders.restore', $tour))->assertSessionHas('success');

    expect(CloudFolder::query()->whereKey([$tour->id, $cologne->id])->count())->toBe(2)
        ->and(CloudFile::query()->whereKey($photo->id)->exists())->toBeTrue()
        ->and($earlier->refresh()->trashed())->toBeTrue();
});

it('restores a file into the next existing folder when its folder is gone', function () {
    $band = Band::factory()->create();
    $tour = CloudFolder::factory()->for($band)->create();
    $cologne = CloudFolder::factory()->inFolder($tour)->create();
    $file = CloudFile::factory()->inFolder($cologne)->withFile()->create();

    $file->delete();
    $this->travel(1)->minute();
    app(TrashCloudFolderAction::class)->execute($cologne);

    actingAsBandManager(cloudBandUser($band))->post(route('band-manager.cloud.trash.files.restore', $file))->assertSessionHas('success');

    expect($file->refresh()->trashed())->toBeFalse()
        ->and($file->folder_id)->toBe($tour->id);
});

it('deletes files for good when emptying the trash', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $inFolder = CloudFile::factory()->inFolder($folder)->withFile('a.txt')->create();
    $loose = CloudFile::factory()->for($band)->withFile('b.txt')->create();
    $kept = CloudFile::factory()->for($band)->withFile('c.txt')->create();
    $paths = [cloudMedia($inFolder)->getPathRelativeToRoot(), cloudMedia($loose)->getPathRelativeToRoot()];

    app(TrashCloudFolderAction::class)->execute($folder);
    $loose->delete();

    actingAsBandManager(cloudBandUser($band))->delete(route('band-manager.cloud.trash.empty', $band))->assertSessionHas('success');

    expect(CloudFile::query()->withTrashed()->pluck('id')->all())->toBe([$kept->id])
        ->and(CloudFolder::query()->withTrashed()->whereKey($folder->id)->exists())->toBeFalse();

    foreach ($paths as $path) {
        Storage::disk('band-manager')->assertMissing($path);
    }
});

it('purges only trash older than the configured days', function () {
    $band = Band::factory()->create();
    $old = CloudFile::factory()->for($band)->withFile('alt.txt')->create();
    $recent = CloudFile::factory()->for($band)->withFile('neu.txt')->create();

    $old->delete();
    $this->travel(31)->days();
    $recent->delete();

    $this->artisan('band-manager:cloud-purge-trash')->assertSuccessful();

    expect(CloudFile::query()->withTrashed()->pluck('id')->all())->toBe([$recent->id]);
});

it('removes the cloud files when a band is deleted', function () {
    $band = Band::factory()->create();
    $file = CloudFile::factory()->inFolder(CloudFolder::factory()->for($band)->create())->withFile()->create();
    $trashed = CloudFile::factory()->for($band)->withFile('weg.txt')->create();
    $paths = [cloudMedia($file)->getPathRelativeToRoot(), cloudMedia($trashed)->getPathRelativeToRoot()];
    $trashed->delete();

    actingAsBandManager()->delete(route('band-manager.admin.bands.destroy', $band))->assertSessionHas('success');

    expect(CloudFile::query()->withTrashed()->count())->toBe(0)
        ->and(CloudFolder::query()->withTrashed()->count())->toBe(0);

    foreach ($paths as $path) {
        Storage::disk('band-manager')->assertMissing($path);
    }
});

it('creates the default folders for new bands', function () {
    actingAsBandManager()->post(route('band-manager.admin.bands.store'), ['name' => 'Neue Band']);

    expect(Band::query()->where('name', 'Neue Band')->sole()->cloudFolders()->where('is_preset', true)->exists())->toBeTrue();
});
