<?php

namespace App\Models\BandManager;

use App\Enums\BandManager\AvailabilityStatusEnum;
use Database\Factories\BandManager\AvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ampel-Status einer Band an einem Tag. "date" bleibt bewusst ein Y-m-d-String,
 * damit Vergleiche auf SQLite und MariaDB gleich funktionieren.
 */
#[Table('bm_availabilities')]
#[Fillable(['band_id', 'date', 'status', 'note', 'updated_by'])]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AvailabilityStatusEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }
}
