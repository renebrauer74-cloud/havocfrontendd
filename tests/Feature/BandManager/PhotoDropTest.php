<?php

use App\Actions\BandManager\Cloud\TrashCloudFolderAction;
use App\Enums\BandManager\CloudLinkTypeEnum;
use App\Enums\BandManager\UserRoleEnum;
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

function photoDropBandUser(Band $band): User
{
    $user = User::factory()->create();
    $band->users()->attach($user);

    return $user;
}

it('shows speicher and photo drop under the cloud menu', function () {
    $band = Band::factory()->create();

    actingAsBandManager(photoDropBandUser($band))
        ->get(route('band-manager.photo-drops.band', $band))
        ->assertOk()
        ->assertSeeInOrder(['Cloud', 'Speicher', 'Photo Drop'])
        ->assertSee('Noch keine Photo Drops');
});

it('creates a folder with an upload link in one step', function () {
    $band = Band::factory()->create();
    $user = photoDropBandUser($band);

    $page = actingAsBandManager($user)->get(route('band-manager.photo-drops.band', $band))->assertOk();
    $photos = $band->cloudFolders()->whereNull('parent_id')->where('name', 'Fotos')->sole();
    $page->assertSee('<option value="'.$photos->id.'" selected', false);

    actingAsBandManager($user)
        ->post(route('band-manager.photo-drops.store', $band), [
            'name' => ' Gig Gloria Köln ',
            'parent_id' => $photos->id,
            'expires_in' => 30,
            'message' => 'Danke fürs Kommen!',
        ])
        ->assertRedirect(route('band-manager.photo-drops.band', $band))
        ->assertSessionHas('new_link_id');

    $link = $band->cloudLinks()->sole();

    expect($link->type)->toBe(CloudLinkTypeEnum::Upload)
        ->and($link->folder->name)->toBe('Gig Gloria Köln')
        ->and($link->folder->parent_id)->toBe($photos->id)
        ->and($link->folder->created_by)->toBe($user->id)
        ->and($link->message)->toBe('Danke fürs Kommen!')
        ->and($link->allow_all_types)->toBeFalse()
        ->and($link->expires_at->isSameDay(now()->addDays(30)))->toBeTrue()
        ->and(ActivityLog::query()->where('action', 'cloud.link_created')->exists())->toBeTrue();

    actingAsBandManager($user)
        ->get(route('band-manager.photo-drops.band', $band))
        ->assertSee('Gig Gloria Köln')
        ->assertSee(route('band-manager.share.show', $link))
        ->assertSee('0 Dateien hochgeladen');

    $this->get(route('band-manager.share.show', $link))->assertOk()->assertSee('Photo Drop von')->assertSee('Danke fürs Kommen!');
    $this->postJson(route('band-manager.share.upload', $link), ['file' => UploadedFile::fake()->image('buehne.jpg')])->assertCreated();

    actingAsBandManager($user)->get(route('band-manager.photo-drops.band', $band))->assertSee('1 Datei hochgeladen');
});

it('needs a free folder name', function () {
    $band = Band::factory()->create();
    CloudFolder::factory()->for($band)->create(['name' => 'Gig Köln']);
    $user = photoDropBandUser($band);

    actingAsBandManager($user)
        ->post(route('band-manager.photo-drops.store', $band), ['name' => '', 'expires_in' => 0])
        ->assertSessionHasErrors('name');

    actingAsBandManager($user)
        ->post(route('band-manager.photo-drops.store', $band), ['name' => 'Gig Köln', 'expires_in' => 0])
        ->assertSessionHasErrors(['name' => 'Hier gibt es schon einen Ordner „Gig Köln“.']);

    actingAsBandManager($user)
        ->post(route('band-manager.photo-drops.store', $band), ['name' => 'Gig Bonn', 'expires_in' => 365])
        ->assertSessionHasErrors('expires_in');

    actingAsBandManager($user)
        ->post(route('band-manager.photo-drops.store', $band), ['name' => 'Gig Bonn', 'parent_id' => CloudFolder::factory()->create()->id, 'expires_in' => 0])
        ->assertSessionHasErrors('parent_id');

    expect(CloudLink::query()->count())->toBe(0);
});

it('lets only band logins with share rights use photo drop', function () {
    $band = Band::factory()->create();
    $member = User::factory()->bandAccess(UserRoleEnum::BandMember, ['cloud' => 'edit'])->create();
    $band->users()->attach($member);
    $otherBand = photoDropBandUser(Band::factory()->create());

    actingAsBandManager($member)->get(route('band-manager.photo-drops.band', $band))->assertForbidden();
    actingAsBandManager($member)->post(route('band-manager.photo-drops.store', $band), ['name' => 'Gig', 'expires_in' => 0])->assertForbidden();
    actingAsBandManager($otherBand)->get(route('band-manager.photo-drops.band', $band))->assertForbidden();
    actingAsBandManager($otherBand)->post(route('band-manager.photo-drops.store', $band), ['name' => 'Gig', 'expires_in' => 0])->assertForbidden();

    actingAsBandManager($member)->get(route('band-manager.cloud.band', $band))->assertOk()->assertDontSee('Photo Drop');

    expect(CloudLink::query()->count())->toBe(0);
});

it('ends a photo drop but keeps the folder and its photos', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create(['name' => 'Gig Köln']);
    $link = CloudLink::factory()->forFolder($folder)->upload()->create();
    $file = CloudFile::factory()->inFolder($folder)->withFile('buehne.jpg')->create(['link_id' => $link->id, 'uploader_name' => 'Lisa']);
    $readLink = CloudLink::factory()->forFolder($folder)->create();
    $user = photoDropBandUser($band);

    actingAsBandManager($user)->delete(route('band-manager.photo-drops.destroy', $readLink))->assertNotFound();
    actingAsBandManager(photoDropBandUser(Band::factory()->create()))->delete(route('band-manager.photo-drops.destroy', $link))->assertForbidden();

    actingAsBandManager($user)
        ->delete(route('band-manager.photo-drops.destroy', $link))
        ->assertRedirect(route('band-manager.photo-drops.band', $band))
        ->assertSessionHas('success');

    expect(CloudLink::query()->whereKey($link->id)->exists())->toBeFalse()
        ->and($folder->fresh())->not->toBeNull()
        ->and($file->fresh()->link_id)->toBeNull()
        ->and($file->fresh()->uploader_name)->toBe('Lisa');

    $this->get(route('band-manager.share.show', ['link' => $link->token]))->assertNotFound();
});

it('marks photo drops whose folder is in the trash', function () {
    $band = Band::factory()->create();
    $link = CloudLink::factory()->forFolder(CloudFolder::factory()->for($band)->create())->upload()->create();
    app(TrashCloudFolderAction::class)->execute($link->folder);

    actingAsBandManager(photoDropBandUser($band))
        ->get(route('band-manager.photo-drops.band', $band))
        ->assertSee('Ordner im Papierkorb')
        ->assertDontSee(route('band-manager.share.show', $link));
});

it('offers a qr code for each active photo drop', function () {
    $band = Band::factory()->create();
    $link = CloudLink::factory()->forFolder(CloudFolder::factory()->for($band)->create(['name' => 'Gig Gloria Köln']))->upload()->create();
    $user = photoDropBandUser($band);

    actingAsBandManager($user)
        ->get(route('band-manager.photo-drops.band', $band))
        ->assertSee(route('band-manager.photo-drops.qr-code', [$link, 'download' => 1]));

    $preview = actingAsBandManager($user)->get(route('band-manager.photo-drops.qr-code', $link));

    $preview->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeaderMissing('Content-Disposition');
    expect(substr($preview->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");

    actingAsBandManager($user)
        ->get(route('band-manager.photo-drops.qr-code', [$link, 'download' => 1]))
        ->assertOk()
        ->assertDownload('photo-drop-gig-gloria-koeln.png');
});

it('hides the qr code from others and for unusable links', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $upload = CloudLink::factory()->forFolder($folder)->upload()->create();
    $read = CloudLink::factory()->forFolder($folder)->create();
    $expired = CloudLink::factory()->forFolder($folder)->upload()->expired()->create();
    $member = User::factory()->bandAccess(UserRoleEnum::BandMember, ['cloud' => 'edit'])->create();
    $band->users()->attach($member);
    $user = photoDropBandUser($band);

    actingAsBandManager($user)->get(route('band-manager.photo-drops.qr-code', $read))->assertNotFound();
    actingAsBandManager($user)->get(route('band-manager.photo-drops.qr-code', $expired))->assertNotFound();
    actingAsBandManager($member)->get(route('band-manager.photo-drops.qr-code', $upload))->assertForbidden();
    actingAsBandManager(photoDropBandUser(Band::factory()->create()))->get(route('band-manager.photo-drops.qr-code', $upload))->assertForbidden();
});

it('keeps upload links out of the speicher share list', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $readLink = CloudLink::factory()->forFolder($folder)->create();
    $uploadLink = CloudLink::factory()->forFolder($folder)->upload()->create();

    actingAsBandManager(photoDropBandUser($band))
        ->get(route('band-manager.cloud.links', $band))
        ->assertSee(route('band-manager.share.show', $readLink))
        ->assertDontSee(route('band-manager.share.show', $uploadLink));
});
