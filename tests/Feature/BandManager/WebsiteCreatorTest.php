<?php

use App\Actions\BandManager\DeleteBandAction;
use App\Enums\BandManager\UserRoleEnum;
use App\Enums\BandManager\WebsiteSectionEnum;
use App\Enums\BandManager\WebsiteThemeEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\ActivityLog;
use App\Models\BandManager\Band;
use App\Models\BandManager\BandProfile;
use App\Models\BandManager\BandWebsite;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('band-manager');
});

/**
 * Formulardaten des Website Creators – alle Abschnitte in Standard-Reihenfolge eingeblendet.
 *
 * @return array<string, mixed>
 */
function websiteFormData(array $overrides = []): array
{
    return array_merge([
        'slug' => 'iron-kittens',
        'is_published' => '0',
        'theme' => 'neon',
        'font' => 'grotesk',
        'hero_style' => 'typo',
        'accent_color' => null,
        'effect_grain' => '1',
        'effect_animations' => '1',
        'language' => 'de',
        'tagline' => 'Lauter als der Nachbar erlaubt',
        'sections' => WebsiteSectionEnum::defaults(),
        'show_agency_booking' => '1',
    ], $overrides);
}

it('shows the website creator below the website builder heading in the menu', function () {
    actingAsBandManager()
        ->get(route('band-manager.website-creator.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Rider Creator', 'Stage Plot Designer', 'Website Builder', 'Website Creator', 'Cloud']);
});

it('sends artists with one band straight to their website creator', function () {
    $artist = User::factory()->create();
    $band = Band::factory()->hasAttached($artist)->create();

    actingAsBandManager($artist)
        ->get(route('band-manager.website-creator.index'))
        ->assertRedirect(route('band-manager.website-creator.band', $band));
});

it('shows every theme, font, hero style and section and suggests the address from the band name', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);
    BandWebsite::factory()->create(['slug' => 'iron-kittens']);

    $response = actingAsBandManager()
        ->get(route('band-manager.website-creator.band', $band))
        ->assertSuccessful()
        ->assertSee('value="iron-kittens-2"', false)
        ->assertSee('Speichern')
        ->assertSee(route('band-manager.website-creator.show', $band));

    foreach (WebsiteThemeEnum::cases() as $theme) {
        $response->assertSee('name="theme" value="'.$theme->value.'"', false)->assertSee($theme->label());
    }

    foreach (WebsiteSectionEnum::cases() as $section) {
        $response->assertSee('value="'.$section->value.'" data-wc-section-key', false);
    }
});

it('saves design, section order and photo selection and publishes the website', function () {
    $band = Band::factory()->create();
    $first = GalleryImage::factory()->withPhoto(400, 300)->create(['band_id' => $band->id]);
    $second = GalleryImage::factory()->withPhoto(400, 300)->create(['band_id' => $band->id]);

    actingAsBandManager()
        ->put(route('band-manager.website-creator.update', $band), websiteFormData([
            'is_published' => '1',
            'accent_color' => '#22C55E',
            'effect_grain' => '0',
            'hero_image_id' => $second->id,
            'gallery_image_ids' => [$second->id],
            'sections' => [
                ['key' => 'shows', 'enabled' => '1'],
                ['key' => 'about', 'enabled' => '0'],
            ],
            'spotify_url' => 'https://open.spotify.com/intl-de/artist/4Z8W4fKeB5YxbusRsdQVPb?si=abc',
        ]))
        ->assertRedirect(route('band-manager.website-creator.band', $band))
        ->assertSessionHas('success', 'Eure Website ist jetzt online! 🎸');

    $website = $band->website()->first();

    expect($website->is_published)->toBeTrue()
        ->and($website->published_at)->not->toBeNull()
        ->and($website->theme)->toBe(WebsiteThemeEnum::Neon)
        ->and($website->accent_color)->toBe('#22c55e')
        ->and($website->effect_grain)->toBeFalse()
        ->and($website->hero_image_id)->toBe($second->id)
        ->and($website->gallery_image_ids)->toBe([$second->id])
        ->and(array_column($website->sections, 'key'))->toBe(['shows', 'about', 'music', 'gallery', 'video', 'members', 'links', 'contact'])
        ->and($website->sections[1])->toBe(['key' => 'about', 'enabled' => false])
        ->and(ActivityLog::query()->where('action', 'website.saved')->exists())->toBeTrue();

    actingAsBandManager()
        ->put(route('band-manager.website-creator.update', $band), websiteFormData(['is_published' => '0']))
        ->assertSessionHas('success', 'Website wurde gespeichert und ist jetzt offline.');

    expect($website->fresh()->is_published)->toBeFalse()
        ->and($website->fresh()->gallery_image_ids)->toBe([])
        ->and($first->exists)->toBeTrue();
});

it('renders unsaved changes in the live preview without saving them', function () {
    $band = Band::factory()->create(['name' => 'Iron Kittens']);

    actingAsBandManager()
        ->postJson(route('band-manager.website-creator.preview', $band), websiteFormData(['theme' => 'zine', 'tagline' => 'Nur ein Test']))
        ->assertSuccessful()
        ->assertSee('bw-theme-zine', false)
        ->assertSee('Nur ein Test')
        ->assertSee('Iron Kittens');

    expect(BandWebsite::query()->count())->toBe(0);
});

it('shows the saved website as full page preview even while it is offline', function () {
    $website = BandWebsite::factory()->create(['theme' => WebsiteThemeEnum::Doom, 'tagline' => 'Aus dem Nebel']);

    actingAsBandManager()
        ->get(route('band-manager.website-creator.show', $website->band))
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('bw-theme-doom', false)
        ->assertSee('Aus dem Nebel');
});

it('rejects invalid addresses, taken addresses, foreign photos, wrong links and colors', function () {
    $band = Band::factory()->create();
    BandWebsite::factory()->create(['slug' => 'vergeben']);
    $foreignImage = GalleryImage::factory()->create();

    actingAsBandManager()
        ->put(route('band-manager.website-creator.update', $band), websiteFormData([
            'slug' => 'Iron Kittens!',
            'accent_color' => 'red',
            'theme' => 'disco',
            'hero_image_id' => $foreignImage->id,
            'gallery_image_ids' => [$foreignImage->id],
            'sections' => [['key' => 'karaoke', 'enabled' => '1']],
            'spotify_url' => 'https://www.deezer.com/artist/123',
            'youtube_url' => 'https://www.youtube.com/@ironkittens',
        ]))
        ->assertSessionHasErrors(['slug', 'accent_color', 'theme', 'hero_image_id', 'gallery_image_ids.0', 'sections.0.key', 'spotify_url', 'youtube_url']);

    actingAsBandManager()
        ->put(route('band-manager.website-creator.update', $band), websiteFormData(['slug' => 'vergeben']))
        ->assertSessionHasErrors(['slug' => 'Diese Adresse ist schon vergeben – bitte eine andere wählen.']);

    expect($band->website()->exists())->toBeFalse();
});

it('serves photos, member photos and the logo of the own band in the preview only', function () {
    $band = Band::factory()->create();
    $image = GalleryImage::factory()->withPhoto(400, 300)->create(['band_id' => $band->id]);
    $foreignImage = GalleryImage::factory()->withPhoto(400, 300)->create();
    $media = $image->getFirstMedia(MediaCollectionEnum::BandPhotos->value);

    actingAsBandManager()
        ->get(route('band-manager.website-creator.media', [$band, $media]))
        ->assertSuccessful()
        ->assertHeader('Cache-Control', 'no-store, private');

    actingAsBandManager()
        ->get(route('band-manager.website-creator.media', [$band, $foreignImage->getFirstMedia(MediaCollectionEnum::BandPhotos->value)]))
        ->assertNotFound();
});

it('keeps the website creator of other bands private', function () {
    $artist = User::factory()->create();
    $website = BandWebsite::factory()->create();
    $band = $website->band;

    actingAsBandManager($artist)->get(route('band-manager.website-creator.band', $band))->assertForbidden();
    actingAsBandManager($artist)->get(route('band-manager.website-creator.show', $band))->assertForbidden();
    actingAsBandManager($artist)->post(route('band-manager.website-creator.preview', $band), websiteFormData())->assertForbidden();
    actingAsBandManager($artist)->put(route('band-manager.website-creator.update', $band), websiteFormData())->assertForbidden();
});

it('lets band accesses with view rights look at but not change the website', function () {
    $band = Band::factory()->create();
    $viewer = User::factory()->bandAccess(UserRoleEnum::BandMember, ['website' => 'view'])->hasAttached($band)->create();
    $stranger = User::factory()->bandAccess(UserRoleEnum::BandMember, ['gallery' => 'edit'])->hasAttached($band)->create();

    actingAsBandManager($viewer)->get(route('band-manager.website-creator.band', $band))->assertSuccessful()->assertDontSee('Speichern');
    actingAsBandManager($viewer)->put(route('band-manager.website-creator.update', $band), websiteFormData())->assertForbidden();
    actingAsBandManager($stranger)->get(route('band-manager.website-creator.band', $band))->assertForbidden();
});

it('lists data from the band manager as sources in the editor', function () {
    $band = Band::factory()->create();
    BandProfile::query()->create(['band_id' => $band->id, 'bio_de' => 'Laut.', 'links' => ['instagram' => 'https://www.instagram.com/ironkittens/']]);

    actingAsBandManager()
        ->get(route('band-manager.website-creator.band', $band))
        ->assertSuccessful()
        ->assertSeeInOrder(['Biografie', 'vorhanden', 'Logo', 'fehlt', 'Fotos', '0 in der Galerie', 'Links', '1 Plattformen']);
});

it('shows the address the band has to forward its own domain to', function () {
    $website = BandWebsite::factory()->published()->create(['slug' => 'iron-kittens']);

    $response = actingAsBandManager()
        ->get(route('band-manager.website-creator.band', $website->band))
        ->assertSuccessful()
        ->assertSeeInOrder(['Eigene Domain verbinden', route('band-websites.show', 'iron-kittens'), 'Weiterleitung']);

    expect($response->getContent())->toMatch('/data-wc-domain-offline\s+hidden/');
});

it('warns that forwarding only works once the website is online', function () {
    $website = BandWebsite::factory()->create(['slug' => 'iron-kittens']);

    $response = actingAsBandManager()
        ->get(route('band-manager.website-creator.band', $website->band))
        ->assertSuccessful()
        ->assertSee(route('band-websites.show', 'iron-kittens'))
        ->assertSee('Die Weiterleitung funktioniert erst, wenn eure Website');

    expect($response->getContent())->not->toMatch('/data-wc-domain-offline\s+hidden/');
});

it('deletes the website together with the band', function () {
    $website = BandWebsite::factory()->create();

    app(DeleteBandAction::class)->execute($website->band);

    expect(BandWebsite::query()->count())->toBe(0);
});
