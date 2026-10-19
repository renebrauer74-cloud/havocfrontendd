<?php

namespace App\Actions\BandManager\Website;

use App\Actions\BandManager\Profile\ListProfileLinksAction;
use App\Enums\BandManager\GigStatusEnum;
use App\Enums\BandManager\PlatformEnum;
use App\Enums\BandManager\WebsiteBodyFontEnum;
use App\Enums\BandManager\WebsiteFontEnum;
use App\Enums\BandManager\WebsiteLanguageEnum;
use App\Enums\BandManager\WebsiteSectionEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\BandWebsite;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Alles, was die Band-Website anzeigt – live aus dem Band-Manager: Bandprofil (Biografie, Logo, Links),
 * Galerie, Bandmitglieder und bestätigte kommende Gigs – ohne das, was die Band abgewählt hat (Logo, hidden_*).
 * Dazu Schriften und Überschriften (eigene oder Standardtext in der Sprache der Website). Abschnitte ohne Inhalt fallen weg.
 * "media_ids" sind die Dateien, die die öffentliche Website ausliefern darf.
 */
class CollectWebsiteContentAction
{
    public function __construct(
        private ListWebsiteSectionsAction $listSections,
        private ListProfileLinksAction $listLinks,
        private ResolveSpotifyEmbedUrlAction $resolveSpotify,
        private ResolveYouTubeEmbedUrlAction $resolveYouTube,
    ) {}

    /**
     * @return array{
     *     colors: array{bg: string, surface: string, text: string, muted: string, accent: string},
     *     heading_font: WebsiteFontEnum,
     *     body_font: WebsiteBodyFontEnum,
     *     titles: array<string, string>,
     *     intros: array<string, string>,
     *     tagline: ?string,
     *     about: string,
     *     logo: ?Media,
     *     hero: ?Media,
     *     gallery: list<array{media: Media, caption: ?string, credit: ?string}>,
     *     members: list<array{name: string, role: ?string, photo: ?Media}>,
     *     shows: Collection<int, Gig>,
     *     links: list<array{platform: PlatformEnum, url: string, display: string}>,
     *     spotify: ?string,
     *     youtube: ?string,
     *     email: ?string,
     *     agency: ?array<string, string>,
     *     imprint: string,
     *     description: string,
     *     sections: list<WebsiteSectionEnum>,
     *     media_ids: list<int>
     * }
     */
    public function execute(BandWebsite $website): array
    {
        $band = $website->band;
        $profile = $band->profile;
        $images = $band->galleryImages()->with('media')->ordered()->get();

        $logo = $profile->exists && $website->show_logo ? $profile->getFirstMedia(MediaCollectionEnum::BandLogos->value) : null;
        $heroImage = $images->firstWhere('id', $website->hero_image_id) ?? $images->firstWhere('is_main', true) ?? $images->first();
        $hero = $heroImage?->getFirstMedia(MediaCollectionEnum::BandPhotos->value);

        $gallery = $this->gallery($images, $website->gallery_image_ids);
        $members = $this->members($band->teamMembers()
            ->with('media')
            ->where('type', TeamMember::TYPE_MEMBER)
            ->whereNotIn('id', $website->hidden_member_ids ?? [])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get());

        $shows = $band->gigs()
            ->upcoming()
            ->where('status', GigStatusEnum::Confirmed)
            ->whereNotIn('id', $website->hidden_gig_ids ?? [])
            ->orderBy('date')
            ->limit((int) config('band-manager.website.max_shows'))
            ->get(['id', 'band_id', 'date', 'title', 'venue', 'city', 'venue_country']);

        $about = md($website->about_text ?: $this->bio($profile->bio_de, $profile->bio_en, $website->language));
        $hiddenLinks = $website->hidden_link_platforms ?? [];
        $links = array_values(array_filter(
            $profile->exists ? $this->listLinks->execute($profile) : [],
            fn (array $link): bool => ! in_array($link['platform']->value, $hiddenLinks, true),
        ));
        $spotify = $this->resolveSpotify->execute($website->spotify_url ?: ($profile->links[PlatformEnum::Spotify->value] ?? null));
        $youtube = $this->resolveYouTube->execute($website->youtube_url);
        $email = $website->contact_email ?: $band->contact_email;
        $agency = $website->show_agency_booking ? array_filter((array) config('band-manager.exports.contact')) : null;

        $hasContent = [
            WebsiteSectionEnum::About->value => $about !== '',
            WebsiteSectionEnum::Music->value => $spotify !== null,
            WebsiteSectionEnum::Shows->value => true,
            WebsiteSectionEnum::Gallery->value => $gallery !== [],
            WebsiteSectionEnum::Video->value => $youtube !== null,
            WebsiteSectionEnum::Members->value => $members !== [],
            WebsiteSectionEnum::Links->value => $links !== [],
            WebsiteSectionEnum::Contact->value => filled($email) || filled($agency),
        ];

        $sections = [];

        foreach ($this->listSections->execute($website->sections) as $entry) {
            if ($entry['enabled'] && $hasContent[$entry['section']->value]) {
                $sections[] = $entry['section'];
            }
        }

        $visible = fn (WebsiteSectionEnum $section): bool => in_array($section, $sections, true);

        $titles = [];
        $intros = [];

        foreach (WebsiteSectionEnum::cases() as $section) {
            $texts = (array) ($website->section_texts[$section->value] ?? []);
            $titles[$section->value] = ($texts['title'] ?? '') ?: __('band-website.sections.'.$section->value, [], $website->language->value);
            $intros[$section->value] = md($texts['intro'] ?? null);
        }

        return [
            'colors' => array_merge($website->theme->colors(), array_filter(['accent' => $website->accent_color])),
            'heading_font' => $website->font,
            'body_font' => $website->body_font ?? $website->font->defaultBodyFont(),
            'titles' => $titles,
            'intros' => $intros,
            'tagline' => $website->tagline ?: $band->genre,
            'about' => $about,
            'logo' => $logo,
            'hero' => $hero,
            'gallery' => $visible(WebsiteSectionEnum::Gallery) ? $gallery : [],
            'members' => $visible(WebsiteSectionEnum::Members) ? $members : [],
            'shows' => $shows,
            'links' => $links,
            'spotify' => $spotify,
            'youtube' => $youtube,
            'email' => $email,
            'agency' => $agency ?: null,
            'imprint' => md($website->imprint),
            'description' => mb_strimwidth(trim((string) preg_replace('/\s+/', ' ', strip_tags($about))) ?: (string) $website->tagline, 0, 160, '…'),
            'sections' => $sections,
            'media_ids' => array_values(array_filter([
                $logo?->id,
                $hero?->id,
                ...array_map(fn (array $item): int => $item['media']->id, $visible(WebsiteSectionEnum::Gallery) ? $gallery : []),
                ...array_map(fn (array $member): ?int => $member['photo']?->id, $visible(WebsiteSectionEnum::Members) ? $members : []),
            ])),
        ];
    }

    /**
     * Biografie in der Sprache der Website – fehlt sie, die andere Sprache.
     */
    private function bio(?string $de, ?string $en, WebsiteLanguageEnum $language): string
    {
        [$first, $second] = $language === WebsiteLanguageEnum::En ? [$en, $de] : [$de, $en];

        return trim((string) $first) !== '' ? (string) $first : (string) $second;
    }

    /**
     * Ausgewählte Fotos in der gewählten Reihenfolge – noch nie ausgewählt (null) = alle Fotos.
     *
     * @param  Collection<int, GalleryImage>  $images
     * @param  array<int>|null  $selectedIds
     * @return list<array{media: Media, caption: ?string, credit: ?string}>
     */
    private function gallery(Collection $images, ?array $selectedIds): array
    {
        $selected = $selectedIds === null
            ? $images
            : collect($selectedIds)->map(fn (mixed $id): ?GalleryImage => $images->firstWhere('id', (int) $id))->filter();

        return $selected
            ->map(fn (GalleryImage $image): ?array => ($media = $image->getFirstMedia(MediaCollectionEnum::BandPhotos->value))
                ? ['media' => $media, 'caption' => $image->caption, 'credit' => $image->credit]
                : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Bandmitglieder mit Künstlername (sonst Vor- und Nachname), Instrument und Foto.
     *
     * @param  Collection<int, TeamMember>  $people
     * @return list<array{name: string, role: ?string, photo: ?Media}>
     */
    private function members(Collection $people): array
    {
        return $people->map(fn (TeamMember $member): array => [
            'name' => $member->stage_name ?: trim($member->first_name.' '.$member->last_name),
            'role' => $member->position ?: null,
            'photo' => $member->getFirstMedia(MediaCollectionEnum::MemberPhotos->value),
        ])->values()->all();
    }
}
