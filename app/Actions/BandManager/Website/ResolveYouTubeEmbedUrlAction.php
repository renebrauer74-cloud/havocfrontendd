<?php

namespace App\Actions\BandManager\Website;

/**
 * YouTube-Video-Link (watch, youtu.be, shorts, live, embed) → Player im erweiterten Datenschutzmodus (youtube-nocookie.com).
 * Kanäle und andere Adressen ergeben null.
 */
class ResolveYouTubeEmbedUrlAction
{
    public function execute(?string $url): ?string
    {
        $patterns = [
            '#^https?://(?:www\.|m\.|music\.)?youtube(?:-nocookie)?\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})(?:[&\#].*)?$#i',
            '#^https?://(?:www\.|m\.)?youtube(?:-nocookie)?\.com/(?:embed|shorts|live)/([A-Za-z0-9_-]{11})(?:[/?&\#].*)?$#i',
            '#^https?://youtu\.be/([A-Za-z0-9_-]{11})(?:[/?&\#].*)?$#i',
        ];

        foreach ($patterns as $pattern) {
            if ($url !== null && preg_match($pattern, trim($url), $matches) === 1) {
                return 'https://www.youtube-nocookie.com/embed/'.$matches[1];
            }
        }

        return null;
    }
}
