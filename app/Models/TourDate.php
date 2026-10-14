<?php

namespace App\Models;

use App\Models\Collections\ShowDateCollection;
use Database\Factories\TourDateFactory;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['artist_id', 'date', 'city', 'venue', 'link'])]
#[CollectedBy(ShowDateCollection::class)]
class TourDate extends Model
{
    /** @use HasFactory<TourDateFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Artist, $this>
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }
}
