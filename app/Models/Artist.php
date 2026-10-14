<?php

namespace App\Models;

use App\Models\Concerns\HasUploadedImage;
use Database\Factories\ArtistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'genre', 'bio', 'bio_en',
    'spotify_url', 'youtube_url', 'website_url', 'instagram_url', 'tiktok_url',
])]
class Artist extends Model
{
    /** @use HasFactory<ArtistFactory> */
    use HasFactory, HasUploadedImage;

    protected static function booted(): void
    {
        // Galeriebilder einzeln löschen, damit ihre Dateien mit entfernt werden.
        static::deleting(function (Artist $artist): void {
            $artist->galleryImages->each->delete();
            $artist->tourDates()->delete();
        });
    }

    /**
     * @return HasMany<TourDate, $this>
     */
    public function tourDates(): HasMany
    {
        return $this->hasMany(TourDate::class);
    }

    /**
     * @return HasMany<GalleryImage, $this>
     */
    public function galleryImages(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('id');
    }

    /**
     * Alle Künstler, natürlich und ohne Groß-/Kleinschreibung nach Namen sortiert.
     *
     * @return Collection<int, Artist>
     */
    public static function alphabetical(): Collection
    {
        return static::query()->get()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Gesetzte Profil-Links, bereits abgesichert.
     *
     * @return array<string, string> Beschriftung => URL
     */
    public function profileLinks(): array
    {
        $links = array_map(safe_link(...), [
            'Official Website' => $this->website_url,
            'Listen on Spotify' => $this->spotify_url,
            'Watch on YouTube' => $this->youtube_url,
            'Instagram' => $this->instagram_url,
            'TikTok' => $this->tiktok_url,
        ]);

        return array_filter($links, fn (string $href): bool => $href !== '#');
    }

    public function displayGenre(): string
    {
        return $this->genre ?: 'Alternative';
    }

    protected function imageFolder(): string
    {
        return 'artists';
    }
}
