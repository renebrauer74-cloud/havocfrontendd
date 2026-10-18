<?php

use App\Enums\MediaCollectionEnum;
use App\Models\AgencyShow;
use App\Models\Artist;
use App\Models\GalleryImage;
use App\Models\News;
use App\Models\TeamMember;
use App\Models\TourDate;
use Illuminate\Support\Facades\Storage;

it('renders every public page while the database is empty', function (string $routeName) {
    $this->get(route($routeName))->assertOk();
})->with(['home', 'roster', 'calendar', 'shows.index', 'team', 'stu', 'contact', 'impressum']);

it('shows the latest two news on top and older ones in the archive', function () {
    News::factory()->create(['title' => 'Oldest']);
    News::factory()->create(['title' => 'Middle']);
    News::factory()->create(['title' => 'Newest']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Newest', 'Middle', 'Previous News', 'Oldest']);
});

it('lists the next four tour dates in chronological order on the home page', function () {
    $artist = Artist::factory()->create(['name' => 'Headliner']);
    foreach (['01.05.2027', '01.01.2027', 'irgendwann', '01.03.2027', '01.02.2027'] as $date) {
        TourDate::factory()->for($artist)->create(['date' => $date, 'city' => "City {$date}"]);
    }

    $this->get(route('home'))
        ->assertSeeInOrder(['City 01.01.2027', 'City 01.02.2027', 'City 01.03.2027', 'City 01.05.2027'])
        ->assertDontSee('City irgendwann')
        ->assertSee('Headliner');
});

it('shows an artist profile with links, gallery and dates', function () {
    $artist = Artist::factory()->create([
        'name' => 'Die Band',
        'bio' => "Zeile eins\nZeile zwei",
        'spotify_url' => 'open.spotify.com/artist/abc',
        'youtube_url' => 'javascript:alert(1)',
    ]);
    Storage::fake('uploads');
    $photo = GalleryImage::factory()->for($artist)->withImage()->create();
    TourDate::factory()->for($artist)->create(['venue' => 'Zeche Bochum']);

    $this->get(route('artists.show', $artist))
        ->assertOk()
        ->assertSee('Die Band')
        ->assertSee('Zeile eins<br>', false)
        ->assertSee('href="https://open.spotify.com/artist/abc"', false)
        ->assertDontSee('javascript:alert(1)')
        ->assertSee($photo->getFirstMediaUrl(MediaCollectionEnum::Gallery->value))
        ->assertSee('Zeche Bochum');
});

it('returns 404 for unknown artists and shows', function () {
    $this->get(route('artists.show', 999))->assertNotFound();
    $this->get(route('shows.show', 999))->assertNotFound();
});

it('only lists artists with tour dates in the calendar, alphabetically', function () {
    Artist::factory()->create(['name' => 'Ohne Termine']);
    TourDate::factory()->for(Artist::factory()->create(['name' => 'zebra']))->create();
    TourDate::factory()->for(Artist::factory()->create(['name' => 'Alpha']))->create();

    $this->get(route('calendar'))
        ->assertSeeInOrder(['Alpha', 'zebra'])
        ->assertDontSee('Ohne Termine');
});

it('shows agency shows and their detail page', function () {
    $show = AgencyShow::factory()->create(['title' => 'Havoc Fest', 'info_text' => '']);

    $this->get(route('shows.index'))->assertSee('Havoc Fest');
    $this->get(route('shows.show', $show))
        ->assertOk()
        ->assertSee('Keine weiteren Details hinterlegt.')
        ->assertSee('data:image/svg+xml', false);
});

it('shows team members', function () {
    TeamMember::factory()->create(['name' => 'Rene', 'phone' => '']);

    $this->get(route('team'))->assertSee('Rene')->assertSee('—');
});

it('permanently redirects the old php urls', function (string $oldUrl, string $newUrl) {
    $this->get($oldUrl)->assertStatus(301)->assertRedirect($newUrl);
})->with([
    ['/roster.php', '/roster'],
    ['/kalender.php', '/kalender'],
    ['/shows.php', '/shows'],
    ['/team.php', '/team'],
    ['/stu.php', '/stu'],
    ['/kontakt.php', '/kontakt'],
    ['/impressum.php', '/impressum'],
    ['/artist.php?id=7', '/artists/7'],
    ['/show-detail.php?id=3', '/shows/3'],
    ['/admin/login.php', '/admin/login'],
]);
