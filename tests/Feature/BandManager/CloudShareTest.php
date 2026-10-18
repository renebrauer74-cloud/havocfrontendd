<?php

use App\Actions\BandManager\Cloud\TrashCloudFolderAction;
use App\Enums\BandManager\UserRoleEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\CloudLink;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake('band-manager');
});

function shareBandUser(Band $band): User
{
    $user = User::factory()->create();
    $band->users()->attach($user);

    return $user;
}

/**
 * Dateinamen im gestreamten ZIP.
 *
 * @return list<string>
 */
function cloudZipEntries(TestResponse $response): array
{
    $path = tempnam(sys_get_temp_dir(), 'zip');
    file_put_contents($path, $response->streamedContent());

    $zip = new ZipArchive;
    $zip->open($path);
    $names = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }

    $zip->close();
    unlink($path);
    sort($names);

    return $names;
}

it('downloads a folder with its subfolders as zip', function () {
    $band = Band::factory()->create();
    $photos = CloudFolder::factory()->for($band)->create(['name' => 'Fotos']);
    $live = CloudFolder::factory()->inFolder($photos)->create(['name' => 'Live']);
    CloudFile::factory()->inFolder($photos)->withFile('cover.txt')->create();
    CloudFile::factory()->inFolder($live)->withFile('koeln.txt')->create();
    CloudFile::factory()->inFolder($live)->withFile('koeln.txt')->create();
    CloudFile::factory()->inFolder($live)->withFile('weg.txt')->create()->delete();
    CloudFile::factory()->for($band)->withFile('oben.txt')->create();

    $response = actingAsBandManager(shareBandUser($band))->get(route('band-manager.cloud.folders.zip', $photos));

    $response->assertOk()->assertDownload('Fotos.zip');
    expect(cloudZipEntries($response))->toBe(['Live/koeln (1).txt', 'Live/koeln.txt', 'cover.txt']);
});

it('downloads the whole cloud of a band as zip', function () {
    $band = Band::factory()->create(['name' => 'Die Ärzte']);
    $photos = CloudFolder::factory()->for($band)->create(['name' => 'Fotos']);
    CloudFile::factory()->inFolder($photos)->withFile('a.txt')->create();
    CloudFile::factory()->for($band)->withFile('b.txt')->create();
    CloudFile::factory()->withFile('fremd.txt')->create();

    $response = actingAsBandManager(shareBandUser($band))->get(route('band-manager.cloud.zip', $band));

    $response->assertOk()->assertDownload('Die Arzte Cloud.zip');
    expect(cloudZipEntries($response))->toBe(['Fotos/a.txt', 'b.txt']);
});

it('refuses zip downloads that are empty or too large', function () {
    $band = Band::factory()->create();
    $empty = CloudFolder::factory()->for($band)->create();
    $full = CloudFolder::factory()->for($band)->create();
    CloudFile::factory()->inFolder($full)->withFile()->create();
    $user = shareBandUser($band);

    actingAsBandManager($user)->get(route('band-manager.cloud.folders.zip', $empty))
        ->assertRedirect(route('band-manager.cloud.folder', $empty))
        ->assertSessionHas('info');

    config(['band-manager.cloud.zip_max_gb' => 0]);

    actingAsBandManager($user)->get(route('band-manager.cloud.folders.zip', $full))
        ->assertRedirect(route('band-manager.cloud.folder', $full))
        ->assertSessionHas('error');
});

it('creates share links for folders and files', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $file = CloudFile::factory()->for($band)->withFile()->create();
    $user = shareBandUser($band);

    actingAsBandManager($user)
        ->post(route('band-manager.cloud.links.folder', $folder), ['expires_in' => 30])
        ->assertRedirect(route('band-manager.cloud.links', $band))
        ->assertSessionHas('new_link_id');

    actingAsBandManager($user)
        ->post(route('band-manager.cloud.links.file', $file), ['expires_in' => 0])
        ->assertRedirect(route('band-manager.cloud.links', $band));

    actingAsBandManager($user)
        ->post(route('band-manager.cloud.links.file', $file), ['expires_in' => 365])
        ->assertSessionHasErrors('expires_in');

    $folderLink = $band->cloudLinks()->where('folder_id', $folder->id)->sole();
    $fileLink = $band->cloudLinks()->where('file_id', $file->id)->sole();

    expect(strlen($folderLink->token))->toBe(40)
        ->and($folderLink->expires_at->isSameDay(now()->addDays(30)))->toBeTrue()
        ->and($fileLink->expires_at)->toBeNull()
        ->and(ActivityLog::query()->where('action', 'cloud.link_created')->count())->toBe(2);

    actingAsBandManager($user)
        ->get(route('band-manager.cloud.links', $band))
        ->assertOk()
        ->assertSee(route('band-manager.share.show', $folderLink))
        ->assertSee('unbegrenzt');
});

it('lets only band logins and the agency create share links', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $member = User::factory()->bandAccess(UserRoleEnum::BandMember, ['cloud' => 'edit'])->create();
    $band->users()->attach($member);
    $otherBand = shareBandUser(Band::factory()->create());

    actingAsBandManager($member)->post(route('band-manager.cloud.links.folder', $folder), ['expires_in' => 7])->assertForbidden();
    actingAsBandManager($otherBand)->post(route('band-manager.cloud.links.folder', $folder), ['expires_in' => 7])->assertForbidden();
    actingAsBandManager()->post(route('band-manager.cloud.links.folder', $folder), ['expires_in' => 7])->assertRedirect();

    expect(CloudLink::query()->count())->toBe(1);
});

it('shows a shared folder with its subfolders to guests', function () {
    $band = Band::factory()->create(['name' => 'Havoc Band']);
    $photos = CloudFolder::factory()->for($band)->create(['name' => 'Fotos']);
    $live = CloudFolder::factory()->inFolder($photos)->create(['name' => 'Live']);
    $inside = CloudFile::factory()->inFolder($live)->withFile('koeln.txt')->create();
    $secret = CloudFolder::factory()->for($band)->create(['name' => 'Verträge']);
    $outside = CloudFile::factory()->inFolder($secret)->withFile('geheim.txt')->create();
    $link = CloudLink::factory()->forFolder($photos)->create();

    $this->get(route('band-manager.share.show', $link))
        ->assertOk()
        ->assertSee('Havoc Band')
        ->assertSee('Live')
        ->assertDontSee('Verträge');

    $this->get(route('band-manager.share.folder', [$link, $live]))->assertOk()->assertSee('koeln.txt');
    $this->get(route('band-manager.share.file', [$link, $inside, 'download' => 1]))->assertOk()->assertDownload('koeln.txt');
    $this->get(route('band-manager.share.folder', [$link, $secret]))->assertNotFound();
    $this->get(route('band-manager.share.file', [$link, $outside]))->assertNotFound();
    $this->get(route('band-manager.share.folder.zip', [$link, $secret]))->assertNotFound();

    expect(cloudZipEntries($this->get(route('band-manager.share.zip', $link))))->toBe(['Live/koeln.txt'])
        ->and($link->refresh()->last_used_at)->not->toBeNull();
});

it('shares a single file only', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $file = CloudFile::factory()->inFolder($folder)->withFile('rider.txt')->create();
    $neighbour = CloudFile::factory()->inFolder($folder)->withFile('nachbar.txt')->create();
    $link = CloudLink::factory()->forFile($file)->create();

    $this->get(route('band-manager.share.show', $link))->assertOk()->assertSee('rider.txt')->assertDontSee('nachbar.txt');
    $this->get(route('band-manager.share.file', [$link, $file]))->assertOk();
    $this->get(route('band-manager.share.file', [$link, $neighbour]))->assertNotFound();
    $this->get(route('band-manager.share.folder', [$link, $folder]))->assertNotFound();
    $this->get(route('band-manager.share.zip', $link))->assertNotFound();
});

it('stops working when expired, revoked or trashed', function () {
    $band = Band::factory()->create();
    $folder = CloudFolder::factory()->for($band)->create();
    $expired = CloudLink::factory()->forFolder($folder)->expired()->create();
    $revoked = CloudLink::factory()->forFolder($folder)->create();
    $trashed = CloudLink::factory()->forFolder(CloudFolder::factory()->for($band)->create())->create();

    actingAsBandManager(shareBandUser($band))->delete(route('band-manager.cloud.links.destroy', $revoked))->assertSessionHas('success');
    app(TrashCloudFolderAction::class)->execute($trashed->folder);

    $this->get(route('band-manager.share.show', $expired))->assertNotFound();
    $this->get('/band-manager/s/'.$revoked->token)->assertNotFound();
    $this->get(route('band-manager.share.show', $trashed))->assertNotFound();
    $this->get('/band-manager/s/unbekannt')->assertNotFound();
});

it('removes links together with their target', function () {
    $band = Band::factory()->create();
    $file = CloudFile::factory()->for($band)->withFile()->create();
    CloudLink::factory()->forFile($file)->create();

    $file->forceDelete();

    expect(CloudLink::query()->count())->toBe(0);
});
