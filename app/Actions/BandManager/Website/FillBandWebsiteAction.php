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
            'body_font' => $data['body_font'] ?? null,
            'hero_style' => $data['hero_style'],
            'accent_color' => isset($data['accent_color']) ? strtolower($data['accent_color']) : null,
            'effect_grain' => (bool) ($data['effect_grain'] ?? false),
            'effect_animations' => (bool) ($data['effect_animations'] ?? false),
            'language' => $data['language'],
            'show_logo' => (bool) ($data['show_logo'] ?? true),
            'tagline' => $data['tagline'] ?? null,
            'about_text' => $data['about_text'] ?? null,
            'hero_image_id' => $data['hero_image_id'] ?? null,
            'gallery_image_ids' => array_values(array_unique(array_map('intval', (array) ($data['gallery_image_ids'] ?? [])))),
            'sections' => array_map(
                fn (array $entry): array => ['key' => $entry['section']->value, 'enabled' => $entry['enabled']],
                $this->listSections->execute($data['sections'] ?? null),
            ),
            'section_texts' => $this->sectionTexts((array) ($data['section_texts'] ?? [])),
            'hidden_member_ids' => array_map('intval', $this->switchedOff((array) ($data['members'] ?? []))),
            'hidden_link_platforms' => array_map('strval', $this->switchedOff((array) ($data['links'] ?? []))),
            'hidden_gig_ids' => array_map('intval', $this->switchedOff((array) ($data['gigs'] ?? []))),
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

    /**
     * Eigene Überschriften und Einleitungen – leere Felder fallen weg (dann gilt der Standardtext).
     *
     * @param  array<string, mixed>  $texts
     * @return array<string, array{title?: string, intro?: string}>
     */
    private function sectionTexts(array $texts): array
    {
        $clean = [];

        foreach ($texts as $section => $fields) {
            $fields = array_filter(array_map(fn (mixed $text): string => trim((string) $text), array_intersect_key((array) $fields, ['title' => 1, 'intro' => 1])));

            if ($fields !== []) {
                $clean[(string) $section] = $fields;
            }
        }

        return $clean;
    }

    /**
     * Schlüssel der abgewählten Einträge aus [Schlüssel => 0/1].
     *
     * @param  array<int|string, mixed>  $switches
     * @return list<int|string>
     */
    private function switchedOff(array $switches): array
    {
        return array_keys(array_filter($switches, fn (mixed $on): bool => ! filter_var($on, FILTER_VALIDATE_BOOLEAN)));
    }
}
