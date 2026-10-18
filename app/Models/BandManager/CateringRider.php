<?php

namespace App\Models\BandManager;

use App\Enums\BandManager\CateringBuyoutEnum;
use Database\Factories\BandManager\CateringRiderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catering Rider einer Band (Rider Creator) – ein Datensatz pro Band.
 * Reisegruppe, Mahlzeiten (je App\Enums\BandManager\CateringMealEnum), Getränke und eigene Abschnitte liegen als JSON.
 */
#[Table('bm_catering_riders', key: 'band_id')]
#[WithoutIncrementing]
#[Fillable([
    'band_id', 'contact_name', 'contact_phone', 'contact_email',
    'people', 'diet_notes', 'meals',
    'buyout', 'buyout_amount', 'buyout_notes',
    'drinks', 'drink_notes',
    'dressing_rooms', 'stage_towels', 'shower_towels', 'needs_shower', 'dressing_room_notes',
    'custom_sections', 'notes', 'updated_by',
])]
class CateringRider extends Model
{
    /** @use HasFactory<CateringRiderFactory> */
    use HasFactory;

    /**
     * Neue Rider starten mit „Catering vor Ort“.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'buyout' => 'catering',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'people' => 'array',
            'meals' => 'array',
            'drinks' => 'array',
            'custom_sections' => 'array',
            'buyout' => CateringBuyoutEnum::class,
            'buyout_amount' => 'decimal:2',
            'needs_shower' => 'boolean',
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
