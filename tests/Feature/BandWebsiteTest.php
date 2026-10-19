<?php

use App\Actions\BandManager\Website\ListWebsiteSectionsAction;
use App\Actions\BandManager\Website\ResolveSpotifyEmbedUrlAction;
use App\Actions\BandManager\Website\ResolveYouTubeEmbedUrlAction;
use App\Enums\BandManager\WebsiteSectionEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\BandProfile;
use App\Models\BandManager\BandWebsite;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('band-manager');
});

it('is not reachable while the website is offline', function () {
    $website = BandWebsite::factory()->create();

    $this->get(route('band-websites.show', $website->slug))->assertNotFound();
    $this->get('/sites/gibt-es-nicht')->assertNotFound();
});

it('shows bio, members, links and only confirmed upcoming shows from the band manager', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens', 'genre' => 'Stoner Rock', 'contact_email' => 'band@ironkittens.de']);
    BandProfile::query()->create([
        'band_id' => $band->id,
        'bio_de' => 'Wir sind **laut**.',
        'bio_en' => 'We are loud.',
        'links' => ['instagram' => 'https://www.instagram.com/ironkittens/'],
    ]);
    TeamMember::factory()->create(['band_id' => $band->id, 'first_name' => 'Kim', 'last_name' => 'Kowalski', 'stage_name' => 'Kitty K', 'position' => 'Gitarre']);
    TeamMember::factory()->crew()->create(['band_id' => $band->id, 'first_name' => 'Sam', 'last_name' => 'Soundmann']);
    Gig::factory()->confirmed()->create(['band_id' => $band->id, 'city' => 'Köln', 'venue' => 'Kulturfabrik', 'date' => today()->addWeek()->format('Y-m-d')]);
    Gig::factory()->create(['band_id' => $band->id, 'city' => 'Anfragestadt']);
    Gig::factory()->confirmed()->past()->create(['band_id' => $band->id, 'city' => 'Vergangenheim']);
    $website = BandWebsite::factory()->published()->create(['band_id' => $band->id, 'tagline' => null, 'imprint' => 'Kim Kowalski, Musterstraße 1']);

    $this->get(route('band-websites.show', $website->slug))
        ->assertSuccessful()
        ->assertSee('<title>Iron Kittens – Stoner Rock</title>', false)
        ->assertSee('Wir sind <strong>laut</strong>.', false)
        ->assertDontSee('We are loud.')
        ->assertSee('Kitty K')
        ->assertSee('Gitarre')
        ->assertDontSee('Soundmann')
        ->assertSee('Kulturfabrik')
        ->assertDontSee('Anfragestadt')
        ->assertDontSee('Vergangenheim')
        ->assertSee('https://www.instagram.com/ironkittens/')
        ->assertSee('mailto:band@ironkittens.de')
        ->assertSee('Musterstraße 1')
        ->assertSee('"@type":"MusicGroup"', false);
});

it('uses the english bio and texts on english websites', function () {
    $band = Band::factory()->create();
    BandProfile::query()->create(['band_id' => $band->id, 'bio_de' => 'Wir sind laut.', 'bio_en' => 'We are loud.']);
    $website = BandWebsite::factory()->published()->create(['band_id' => $band->id, 'language' => 'en']);

    $this->get(route('band-websites.show', $website->slug))
        ->assertSuccessful()
        ->assertSee('<html lang="en">', false)
        ->assertSee('We are loud.')
        ->assertSee('Back to top')
        ->assertDontSee('Wir sind laut.');
});

it('shows the sections in the chosen order and leaves out hidden and empty ones', function () {
    $band = Band::factory()->create();
    BandProfile::query()->create(['band_id' => $band->id, 'bio_de' => 'Biografie', 'links' => ['spotify' => 'https://open.spotify.com/artist/4Z8W4fKeB5YxbusRsdQVPb']]);
    $website = BandWebsite::factory()->published()->create([
        'band_id' => $band->id,
        'sections' => [
            ['key' => 'music', 'enabled' => true],
            ['key' => 'shows', 'enabled' => true],
            ['key' => 'about', 'enabled' => true],
            ['key' => 'contact', 'enabled' => false],
        ],
    ]);

    $this->get(route('band-websites.show', $website->slug))
        ->assertSuccessful()
        ->assertSeeInOrder(['id="music"', 'id="shows"', 'id="about"'], false)
        ->assertSee('data-bw-embed="https://open.spotify.com/embed/artist/4Z8W4fKeB5YxbusRsdQVPb"', false)
        ->assertDontSee('id="contact"', false)
        ->assertDontSee('id="gallery"', false)
        ->assertDontSee('id="video"', false);
});

it('serves only the photos shown on the website', function () {
    $band = Band::factory()->create();
    $shown = GalleryImage::factory()->withPhoto(400, 300)->main()->create(['band_id' => $band->id]);
    $hidden = GalleryImage::factory()->withPhoto(400, 300)->create(['band_id' => $band->id, 'sort_order' => 2]);
    $foreign = GalleryImage::factory()->withPhoto(400, 300)->create();
    $website = BandWebsite::factory()->published()->create(['band_id' => $band->id, 'gallery_image_ids' => [$shown->id]]);
    $shownMedia = $shown->getFirstMedia(MediaCollectionEnum::BandPhotos->value);

    $this->get(route('band-websites.show', $website->slug))
        ->assertSee(route('band-websites.media', [$website->slug, $shownMedia]));

    $this->get(route('band-websites.media', [$website->slug, $shownMedia]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/jpeg');
    $this->get(route('band-websites.media', [$website->slug, $hidden->getFirstMedia(MediaCollectionEnum::BandPhotos->value)]))->assertNotFound();
    $this->get(route('band-websites.media', [$website->slug, $foreign->getFirstMedia(MediaCollectionEnum::BandPhotos->value)]))->assertNotFound();

    $website->update(['is_published' => false]);
    $this->get(route('band-websites.media', [$website->slug, $shownMedia]))->assertNotFound();
});

it('falls back to the band name when there is no photo', function () {
    $website = BandWebsite::factory()->published()->create(['hero_style' => 'fullscreen']);

    $this->get(route('band-websites.show', $website->slug))
        ->assertSuccessful()
        ->assertSee('bw-hero-typo', false);
});

it('turns spotify links into player addresses', function (string $url, ?string $embed) {
    expect(app(ResolveSpotifyEmbedUrlAction::class)->execute($url))->toBe($embed);
})->with([
    ['https://open.spotify.com/artist/4Z8W4fKeB5YxbusRsdQVPb', 'https://open.spotify.com/embed/artist/4Z8W4fKeB5YxbusRsdQVPb'],
    ['https://open.spotify.com/intl-de/album/1DFixLWuPkv3KT3TnV35m3?si=x', 'https://open.spotify.com/embed/album/1DFixLWuPkv3KT3TnV35m3'],
    ['https://open.spotify.com/user/someone', null],
    ['https://evil.example/open.spotify.com/artist/4Z8W4fKeB5YxbusRsdQVPb', null],
]);

it('turns youtube video links into privacy friendly player addresses', function (string $url, ?string $embed) {
    expect(app(ResolveYouTubeEmbedUrlAction::class)->execute($url))->toBe($embed);
})->with([
    ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    ['https://youtu.be/dQw4w9WgXcQ?t=10', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
    ['https://www.youtube.com/@ironkittens', null],
]);

it('keeps unknown sections out and appends new ones', function () {
    $website = BandWebsite::factory()->published()->create(['sections' => [['key' => 'karaoke', 'enabled' => true]]]);

    expect(array_map(fn (array $entry) => $entry['section'], app(ListWebsiteSectionsAction::class)->execute($website->sections)))
        ->toBe(WebsiteSectionEnum::cases());
});
