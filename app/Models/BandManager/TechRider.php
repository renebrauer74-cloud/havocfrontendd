<?php

namespace App\Models\BandManager;

use App\Enums\MediaCollectionEnum;
use Database\Factories\BandManager\TechRiderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Technischer Rider einer Band (Rider Creator) – ein Datensatz pro Band.
 * Listen liegen als JSON, der Bühnenplan in der Media Library.
 */
#[Table('bm_tech_riders', key: 'band_id')]
#[WithoutIncrementing]
#[Fillable([
    'band_id', 'contact_name', 'contact_phone', 'contact_email', 'brings_foh_engineer', 'brings_monitor_engineer',
    'setup_minutes', 'soundcheck_minutes', 'show_minutes', 'teardown_minutes',
    'line_up', 'inputs', 'monitors', 'backline', 'custom_sections',
    'pa_notes', 'monitor_notes', 'stage_width', 'stage_depth', 'stage_notes', 'lighting_notes', 'power_notes', 'notes',
    'updated_by',
])]
class TechRider extends Model implements HasMedia
{
    /** @use HasFactory<TechRiderFactory> */
    use HasFactory, InteractsWithMedia;

    /** Dompdf kann nur JPEG und PNG einbetten */
    public const STAGE_PLOT_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'brings_foh_engineer' => 'boolean',
            'brings_monitor_engineer' => 'boolean',
            'line_up' => 'array',
            'inputs' => 'array',
            'monitors' => 'array',
            'backline' => 'array',
            'custom_sections' => 'array',
            'stage_width' => 'decimal:1',
            'stage_depth' => 'decimal:1',
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
     * Der Bühnenplan bleibt unverändert (Skizzen mit feinen Linien und Text).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::StagePlots->value)
            ->singleFile()
            ->useDisk('band-manager')
            ->acceptsMimeTypes(array_keys(self::STAGE_PLOT_TYPES));
    }
}
