<?php

namespace App\Actions\BandManager\Website;

/**
 * Spotify-Link (Künstler, Album, Song, Playlist …) → Adresse des Spotify-Players zum Einbetten.
 * "https://open.spotify.com/intl-de/artist/abc?si=1" → "https://open.spotify.com/embed/artist/abc".
 * Andere Adressen ergeben null.
 */
class ResolveSpotifyEmbedUrlAction
{
    public function execute(?string $url): ?string
    {
        $pattern = '#^https?://open\.spotify\.com/(?:intl-[a-z]{2}(?:-[a-z]{2})?/)?(?:embed/)?(artist|album|track|playlist|episode|show)/([A-Za-z0-9]{10,40})(?:[/?\#].*)?$#i';

        if ($url === null || preg_match($pattern, trim($url), $matches) !== 1) {
            return null;
        }

        return 'https://open.spotify.com/embed/'.strtolower($matches[1]).'/'.$matches[2];
    }
}
