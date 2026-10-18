<?php

use App\Enums\MediaCollectionEnum;
use App\Models\AgencyShow;
use App\Models\Artist;
use App\Models\Message;
use App\Models\News;
use App\Models\TeamMember;
use App\Models\TourDate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('uploads');
});

it('creates, updates and deletes tour dates', function () {
    $artist = Artist::factory()->create();
    $input = ['artist_id' => $artist->id, 'date' => '24.05.2027', 'city' => 'Witten', 'venue' => 'Werk°Stadt', 'link' => ''];

    actingAsAdmin()->post(route('admin.dates.store'), $input)->assertRedirect(route('admin.dates.index'));
    $date = TourDate::sole();
    expect($date->city)->toBe('Witten');

    actingAsAdmin()->get(route('admin.dates.edit', $date))->assertOk()->assertSee('Werk°Stadt');
    actingAsAdmin()->put(route('admin.dates.update', $date), [...$input, 'city' => 'Bochum']);
    expect($date->refresh()->city)->toBe('Bochum');

    actingAsAdmin()->delete(route('admin.dates.destroy', $date));
    $this->assertModelMissing($date);
});

it('rejects tour dates for unknown artists', function () {
    actingAsAdmin()
        ->post(route('admin.dates.store'), ['artist_id' => 999, 'date' => '01.01.2027', 'city' => 'X', 'venue' => 'Y'])
        ->assertSessionHasErrors('artist_id');
});

it('creates, updates and deletes news', function () {
    actingAsAdmin()->post(route('admin.news.store'), ['title' => 'Tour!', 'text' => 'Infos'])->assertRedirect(route('admin.news.index'));
    $news = News::sole();

    actingAsAdmin()->get(route('admin.news.edit', $news))->assertOk();
    actingAsAdmin()->put(route('admin.news.update', $news), ['title' => 'Tour 2027!', 'text' => 'Infos']);
    expect($news->refresh()->title)->toBe('Tour 2027!');

    actingAsAdmin()->delete(route('admin.news.destroy', $news));
    $this->assertModelMissing($news);
});

it('creates, updates and deletes team members', function () {
    $input = ['name' => 'Rene', 'phone' => '', 'email' => 'rene@havoc-agency.com', 'hobbies' => 'Vinyl', 'bio' => ''];

    actingAsAdmin()->post(route('admin.team.store'), $input)->assertRedirect(route('admin.team.index'));
    $member = TeamMember::sole();

    actingAsAdmin()->get(route('admin.team.edit', $member))->assertOk();
    actingAsAdmin()->put(route('admin.team.update', $member), [...$input, 'phone' => '0177']);
    expect($member->refresh()->phone)->toBe('0177');

    actingAsAdmin()->delete(route('admin.team.destroy', $member));
    $this->assertModelMissing($member);
});

it('requires a valid email for team members', function () {
    actingAsAdmin()
        ->post(route('admin.team.store'), ['name' => 'Rene', 'email' => 'keine-mail'])
        ->assertSessionHasErrors(['email' => 'Bitte eine gültige E-Mail-Adresse angeben.']);
});

it('creates, updates and deletes agency shows', function () {
    $input = ['title' => 'Havoc Fest', 'date' => '12.-14. JUNI 2027', 'city' => 'Witten', 'venue' => 'Park', 'ticket_url' => '', 'info_text' => ''];

    actingAsAdmin()->post(route('admin.shows.store'), $input)->assertRedirect(route('admin.shows.index'));
    $show = AgencyShow::sole();

    actingAsAdmin()->get(route('admin.shows.edit', $show))->assertOk();
    actingAsAdmin()->put(route('admin.shows.update', $show), [...$input, 'venue' => 'Halle']);
    expect($show->refresh()->venue)->toBe('Halle');

    actingAsAdmin()->delete(route('admin.shows.destroy', $show));
    $this->assertModelMissing($show);
});

it('removes the image of news, team members and agency shows when requested', function (string $model, string $route, MediaCollectionEnum $collection, array $input) {
    $record = $model::factory()->create();
    $record->addMedia(UploadedFile::fake()->image('bild.jpg'))->toMediaCollection($collection->value);
    $oldFile = $record->getFirstMedia($collection->value)->getPathRelativeToRoot();

    actingAsAdmin()->get(route("admin.{$route}.edit", $record))->assertOk()->assertSee('name="remove_image"', false);

    actingAsAdmin()
        ->put(route("admin.{$route}.update", $record), [...$input, 'remove_image' => '1'])
        ->assertRedirect(route("admin.{$route}.index"));

    expect($record->refresh()->getMedia($collection->value))->toHaveCount(0);
    Storage::disk('uploads')->assertMissing($oldFile);
})->with([
    'news' => [News::class, 'news', MediaCollectionEnum::News, ['title' => 'Tour!', 'text' => 'Infos']],
    'team' => [TeamMember::class, 'team', MediaCollectionEnum::Team, ['name' => 'Rene', 'email' => 'rene@havoc-agency.com']],
    'shows' => [AgencyShow::class, 'shows', MediaCollectionEnum::Shows, ['title' => 'Havoc Fest', 'date' => '12.-14. JUNI 2027', 'city' => 'Witten', 'venue' => 'Park']],
]);

it('lists and deletes contact requests', function () {
    $message = Message::factory()->create(['name' => 'Anfragende Person']);

    actingAsAdmin()->get(route('admin.messages.index'))->assertOk()->assertSee('Anfragende Person');

    actingAsAdmin()->delete(route('admin.messages.destroy', $message))->assertRedirect(route('admin.messages.index'));
    $this->assertModelMissing($message);
});
