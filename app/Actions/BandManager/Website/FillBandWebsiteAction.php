<?php

namespace App\Actions\BandManager\Website;

use App\Models\BandManager\Band;
use App\Models\BandManager\BandWebsite;

/**
 * Überträgt die Eingaben des Website Creators auf die Website einer Band – ohne zu speichern,
 * damit die Live-Vorschau ungespeicherte Änderungen zeigen kann (siehe SaveBandWebsiteAction).
 */
class FillBandWebsiteAction
{
    public function __construct(private ListWebsiteSectionsAction $listSections) {}

    /**
     * @param  array<string, mixed>  $data  validierte Eingaben aus BandWebsiteRequest
     */
    public function execute(Band $band, array $data): BandWebsite
    {
        $website = $band->website;
        $isPublished = (bool) ($data['is_published'] ?? false);

        $website->fill([
            'slug' => $data['slug'],
            'is_published' => $isPublished,
            'published_at' => $isPublished ? ($website->published_at ?? now()) : $website->published_at,
            'theme' => $data['theme'],
            'font' => $data['font'],
            'hero_style' => $data['hero_style'],
            'accent_color' => isset($data['accent_color']) ? strtolower($data['accent_color']) : null,
            'effect_grain' => (bool) ($data['effect_grain'] ?? false),
            'effect_animations' => (bool) ($data['effect_animations'] ?? false),
            'language' => $data['language'],
            'tagline' => $data['tagline'] ?? null,
            'about_text' => $data['about_text'] ?? null,
            'hero_image_id' => $data['hero_image_id'] ?? null,
            'gallery_image_ids' => array_values(array_unique(array_map('intval', (array) ($data['gallery_image_ids'] ?? [])))),
            'sections' => array_map(
                fn (array $entry): array => ['key' => $entry['section']->value, 'enabled' => $entry['enabled']],
                $this->listSections->execute($data['sections'] ?? null),
            ),
            'spotify_url' => $data['spotify_url'] ?? null,
            'youtube_url' => $data['youtube_url'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'show_agency_booking' => (bool) ($data['show_agency_booking'] ?? false),
            'imprint' => $data['imprint'] ?? null,
        ]);

        // Ein vorher geladenes Titelfoto passt evtl. nicht mehr zur neuen Auswahl
        $website->unsetRelation('heroImage');

        return $website;
    }
}
