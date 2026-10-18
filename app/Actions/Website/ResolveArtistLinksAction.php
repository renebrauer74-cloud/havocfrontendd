<?php

namespace App\Actions\Website;

use App\Models\Artist;

/**
 * Gesetzte Profil-Links eines Künstlers, abgesichert über safe_link() – leere und unsichere fallen weg.
 */
class ResolveArtistLinksAction
{
    /**
     * @return array<string, string> Beschriftung => URL
     */
    public function execute(Artist $artist): array
    {
        $links = array_map(safe_link(...), [
            'Official Website' => $artist->website_url,
            'Listen on Spotify' => $artist->spotify_url,
            'Watch on YouTube' => $artist->youtube_url,
            'Instagram' => $artist->instagram_url,
            'TikTok' => $artist->tiktok_url,
        ]);

        return array_filter($links, fn (string $href): bool => $href !== '#');
    }
}
