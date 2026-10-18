<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use Database\Factories\GalleryImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Table('gallery')]
#[Fillable(['artist_id'])]
class GalleryImage extends Model implements HasMedia
{
    /** @use HasFactory<GalleryImageFactory> */
    use HasFactory, InteractsWithMedia;

    public const MAX_PER_ARTIST = 8;

    /**
     * @return BelongsTo<Artist, $this>
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Gallery->value)
            ->singleFile();
    }
}
