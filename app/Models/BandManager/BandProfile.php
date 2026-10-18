<?php

namespace App\Models\BandManager;

use App\Enums\MediaCollectionEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Biografie (DE/EN), Social-Media-/Streaming-Links und Bandlogo – ein Datensatz pro Band.
 * Das Logo liegt in der Media Library.
 */
#[Table('bm_band_profiles', key: 'band_id')]
#[WithoutIncrementing]
#[Fillable(['band_id', 'bio_de', 'bio_en', 'links', 'updated_by'])]
class BandProfile extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** PNG zuerst – wegen transparentem Hintergrund die beste Wahl für Logos */
    public const LOGO_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'links' => 'array',
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
     * Das Logo bleibt unverändert (Transparenz).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::BandLogos->value)
            ->singleFile()
            ->useDisk(config('havoc.uploads.band_manager_disk'))
            ->acceptsMimeTypes(array_keys(self::LOGO_TYPES));
    }
}
