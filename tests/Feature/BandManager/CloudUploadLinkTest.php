<?php

use App\Enums\BandManager\CloudLinkTypeEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\CloudLink;
use App\Models\BandManager\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('band-manager');
});

function uploadLinkBandUser(Band $band): User
{
    $user = User::factory()->create();
    $band->users()->attach($user);

    return $user;
}

it('creates upload links for folders only', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create(['name' => 'Gig Köln']);
    $file = CloudFile::factory()->for($band)->withFile()->create();
    $user = uploadLinkBandUser($band);

    actingAsBandManager($user)
        ->post(route('band-manager.cloud.links.folder', $folder), [
            'expires_in' => 30,
            'type' => 'upload',
            'message' => 'Ladet eure Fotos vom Gig hoch!',
            'allow_all_types' => '1',
        ])
        ->assertRedirect(route('band-manager.cloud.links', $band));

    actingAsBandManager($user)
        ->post(route('band-manager.cloud.links.file', $file), ['expires_in' => 30, 'type' => 'upload', 'allow_all_types' => '1'])
        ->assertRedirect(route('band-manager.cloud.links', $band));

    $folderLink = $band->cloudLinks()->where('folder_id', $folder->id)->sole();
    $fileLink = $band->cloudLinks()->where('file_id', $file->id)->sole();

    expect($folderLink->type)->toBe(CloudLinkTypeEnum::Upload)
        ->and($folderLink->message)->toBe('Ladet eure Fotos vom Gig hoch!')
        ->and($folderLink->allow_all_types)->toBeTrue()
        ->and($fileLink->type)->toBe(CloudLinkTypeEnum::Read)
        ->and($fileLink->allow_all_types)->toBeFalse();
});

it('lets guests upload photos into the shared folder', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create(['name' => 'Gig Köln']);
    CloudFile::factory()->inFolder($folder)->withFile('setlist.txt')->create();
    $link = CloudLink::factory()->forFolder($folder)->upload()->create(['message' => 'Danke fürs Kommen!']);

    $this->get(route('band-manager.share.show', $link))
        ->assertOk()
        ->assertSee('Danke fürs Kommen!')
        ->assertSee('Fotos &amp; Videos hochladen', false)
        ->assertSee('setlist.txt');

    $this->postJson(route('band-manager.share.upload', $link), [
        'file' => UploadedFile::fake()->image('buehne.jpg', 1200, 800),
        'uploader_name' => ' Max Photography ',
    ])->assertCreated()->assertJson(['name' => 'buehne.jpg']);

    $upload = $folder->files()->where('link_id', $link->id)->sole();

    expect($upload->uploader_name)->toBe('Max Photography')
        ->and($upload->uploaded_by)->toBeNull()
        ->and($upload->band_id)->toBe($band->id)
        ->and($upload->getFirstMedia(MediaCollectionEnum::CloudFiles->value)->getPathRelativeToRoot())->toStartWith("cloud/{$band->id}/")
        ->and(ActivityLog::query()->where('action', 'cloud.link_uploaded')->exists())->toBeTrue();

    $this->get(route('band-manager.share.show', $link))->assertSee('buehne.jpg')->assertSee('© Max Photography');

    actingAsBandManager(uploadLinkBandUser($band))
        ->get(route('band-manager.cloud.folder', $folder))
        ->assertSee('über Upload-Link · Max Photography');
});

it('only accepts photos and videos unless all types are allowed', function () {
    $folder = CloudFolder::factory()->create();
    $photosOnly = CloudLink::factory()->forFolder($folder)->upload()->create();
    $allTypes = CloudLink::factory()->forFolder($folder)->upload()->create(['allow_all_types' => true]);
    $pdf = fn () => UploadedFile::fake()->createWithContent('rider.pdf', "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n");

    $this->postJson(route('band-manager.share.upload', $photosOnly), ['file' => $pdf()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file' => 'Hier können nur Fotos und Videos hochgeladen werden.']);

    $this->postJson(route('band-manager.share.upload', $allTypes), ['file' => $pdf()])->assertCreated();

    $this->postJson(route('band-manager.share.upload', $allTypes), ['file' => UploadedFile::fake()->create('virus.exe', 1)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    expect($folder->files()->count())->toBe(1);
});

it('shows all files in the desktop file dialog and accepts iphone photos', function () {
    $folder = CloudFolder::factory()->create();
    $link = CloudLink::factory()->forFolder($folder)->upload()->create();
    $heic = "\x00\x00\x00\x18ftypheic\x00\x00\x00\x00mif1heic".str_repeat("\x00", 64);

    // Der Windows-Dialog zeigt mit accept="image/*,video/*" je nach Rechner keine Fotos – den Filter setzt cloud.js nur auf Handys
    $this->get(route('band-manager.share.show', $link))
        ->assertOk()
        ->assertDontSee(' accept="', false)
        ->assertSee('data-mobile-accept="image/*,video/*"', false);

    $this->postJson(route('band-manager.share.upload', $link), ['file' => UploadedFile::fake()->createWithContent('IMG_0042.HEIC', $heic)])
        ->assertCreated();

    expect($folder->files()->count())->toBe(1);
});

it('refuses uploads over read links, expired links and revoked links', function () {
    $folder = CloudFolder::factory()->create();
    $read = CloudLink::factory()->forFolder($folder)->create();
    $expired = CloudLink::factory()->forFolder($folder)->upload()->expired()->create();
    $revoked = CloudLink::factory()->forFolder($folder)->upload()->create();
    $token = $revoked->token;
    $revoked->delete();

    $this->postJson(route('band-manager.share.upload', $read), ['file' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();
    $this->postJson(route('band-manager.share.upload', $expired), ['file' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();
    $this->postJson("/band-manager/s/{$token}/files", ['file' => UploadedFile::fake()->image('a.jpg')])->assertNotFound();

    $this->get(route('band-manager.share.show', $read))->assertOk()->assertDontSee('hochladen');

    expect(CloudFile::query()->count())->toBe(0);
});

it('tells guests politely when the band has no space left', function () {
    config(['band-manager.cloud.quota_gb' => 1 / 1024 / 1024]);   // 1 KB
    $link = CloudLink::factory()->forFolder(CloudFolder::factory()->create())->upload()->create();

    $this->postJson(route('band-manager.share.upload', $link), ['file' => UploadedFile::fake()->image('gross.jpg', 800, 800)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file' => 'Hier ist gerade kein Platz mehr – bitte gib der Band Bescheid.']);
});

it('limits the number of uploads per visitor', function () {
    config(['band-manager.cloud.link_uploads_per_hour' => 2]);
    $link = CloudLink::factory()->forFolder(CloudFolder::factory()->create())->upload()->create();

    $this->postJson(route('band-manager.share.upload', $link), ['file' => UploadedFile::fake()->image('1.jpg')])->assertCreated();
    $this->postJson(route('band-manager.share.upload', $link), ['file' => UploadedFile::fake()->image('2.jpg')])->assertCreated();
    $this->postJson(route('band-manager.share.upload', $link), ['file' => UploadedFile::fake()->image('3.jpg')])->assertTooManyRequests();
});

it('keeps uploaded files when the link is revoked', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $link = CloudLink::factory()->forFolder($folder)->upload()->create();

    $this->postJson(route('band-manager.share.upload', $link), ['file' => UploadedFile::fake()->image('a.jpg'), 'uploader_name' => 'Lisa']);

    actingAsBandManager(uploadLinkBandUser($band))
        ->get(route('band-manager.photo-drops.band', $band))
        ->assertSee('1 Datei hochgeladen');

    actingAsBandManager(uploadLinkBandUser($band))->delete(route('band-manager.cloud.links.destroy', $link));

    $file = $folder->files()->sole();

    expect($file->link_id)->toBeNull()
        ->and($file->uploader_name)->toBe('Lisa');
});
