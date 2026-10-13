<?php
declare(strict_types=1);

namespace Havoc\Repository;

final class ArtistRepository extends Repository
{
    protected const FIELDS = [
        'name', 'genre', 'bio', 'bio_en', 'image_url',
        'spotify_url', 'youtube_url', 'website_url', 'instagram_url', 'tiktok_url',
    ];

    public function allAlphabetical(): array
    {
        $artists = $this->all();
        usort($artists, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
        return $artists;
    }
}
