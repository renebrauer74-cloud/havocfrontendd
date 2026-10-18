<?php

namespace App\Models\BandManager;

use App\Enums\BandManager\RiderCategoryEnum;
use App\Enums\MediaCollectionEnum;
use Database\Factories\BandManager\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Vertrag oder Rider (PDF). Die Datei liegt in der Media Library.
 */
#[Table('bm_documents')]
#[Fillable(['band_id', 'gig_id', 'type', 'category', 'title', 'uploaded_by'])]
class Document extends Model implements HasMedia
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, InteractsWithMedia;

    public const TYPE_CONTRACT = 'contract';

    public const TYPE_RIDER = 'rider';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RiderCategoryEnum::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Documents->value)
            ->singleFile()
            ->useDisk(config('havoc.uploads.band_manager_disk'))
            ->acceptsMimeTypes(['application/pdf']);
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * @return BelongsTo<Gig, $this>
     */
    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }

    public function isContract(): bool
    {
        return $this->type === self::TYPE_CONTRACT;
    }
}
