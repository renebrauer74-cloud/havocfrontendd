<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use App\Models\Collections\ShowDateCollection;
use Database\Factories\AgencyShowFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'date', 'city', 'venue', 'ticket_url', 'info_text'])]
#[CollectedBy(ShowDateCollection::class)]
class AgencyShow extends Model implements HasMedia
{
    /** @use HasFactory<AgencyShowFactory> */
    use HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Shows->value)
            ->singleFile()
            ->useFallbackUrl(placeholder_image(300, 400, 'HAVOC SHOW'));
    }
}
