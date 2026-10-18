<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use Database\Factories\ArtistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'name', 'genre', 'bio', 'bio_en',
    'spotify_url', 'youtube_url', 'website_url', 'instagram_url', 'tiktok_url',
])]
class Artist extends Model implements HasMedia
{
    /** @use HasFactory<ArtistFactory> */
    use HasFactory, InteractsWithMedia;

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Artists->value)
            ->singleFile()
            ->useFallbackUrl(asset('assets/img/havoc-logo.jpeg'));
    }
}
