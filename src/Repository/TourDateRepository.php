<?php
declare(strict_types=1);

namespace Havoc\Repository;

final class TourDateRepository extends Repository
{
    protected const FIELDS = ['artist_id', 'date', 'city', 'venue', 'link'];

    /** Alle Termine chronologisch, ergänzt um 'artist_name'. */
    public function allWithArtist(ArtistRepository $artists): array
    {
        $names = array_column($artists->all(), 'name', 'id');
        $dates = array_map(static fn (array $d): array => $d + ['artist_name' => $names[$d['artist_id']] ?? '—'], $this->all());
        return sort_by_show_date($dates);
    }

    public function forArtist(int $artistId): array
    {
        return sort_by_show_date($this->table->where('artist_id', $artistId));
    }

    public function deleteForArtist(int $artistId): int
    {
        return $this->table->deleteWhere('artist_id', $artistId);
    }
}
