<?php

namespace App\Models\BandManager;

use App\Enums\BandManager\GalleryCategoryEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use Database\Factories\BandManager\GalleryImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Foto in der Bildergalerie einer Band. Original + Vorschau (max. 1600 px, JPEG) liegen in der Media Library.
 */
#[Table('bm_gallery_images')]
#[Fillable([
    'band_id', 'category', 'caption', 'credit', 'is_main', 'sort_order',
    'uploaded_by',
])]
class GalleryImage extends Model implements HasMedia
{
    /** @use HasFactory<GalleryImageFactory> */
    use HasFactory, InteractsWithMedia;

    public const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => GalleryCategoryEnum::class,
            'is_main' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * Hauptbild zuerst, dann in Upload-Reihenfolge.
     *
     * @param  Builder<GalleryImage>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderByDesc('is_main')->orderBy('sort_order')->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::BandPhotos->value)
            ->singleFile()
            ->useDisk('band-manager')
            ->acceptsMimeTypes(array_keys(self::TYPES));
    }

    /**
     * Vorschau für Web und Pressekit-PDF – direkt im Upload-Request, ohne Queue.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $size = max(400, (int) config('band-manager.gallery.preview_px'));

        $this->addMediaConversion(MediaConversionEnum::Preview->value)
            ->fit(Fit::Max, $size, $size)
            ->format('jpg')
            ->nonQueued();
    }
}
