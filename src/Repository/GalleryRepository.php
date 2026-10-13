<?php
declare(strict_types=1);

namespace Havoc\Repository;

final class GalleryRepository extends Repository
{
    public const MAX_PER_ARTIST = 8;

    protected const FIELDS = ['artist_id', 'image_url'];

    public function forArtist(int $artistId): array
    {
        return $this->table->where('artist_id', $artistId);
    }

    public function deleteForArtist(int $artistId): int
    {
        return $this->table->deleteWhere('artist_id', $artistId);
    }
}
