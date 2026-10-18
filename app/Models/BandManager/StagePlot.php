<?php

namespace App\Models\BandManager;

use App\Enums\MediaCollectionEnum;
use Database\Factories\BandManager\StagePlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Stage Plot einer Band (Rider Creator → Stage Plot Designer) – ein Datensatz pro Band.
 * Elemente (Symbol aus App\Enums\BandManager\StagePlotElementEnum, Position und Größe in cm, Drehung, Beschriftung)
 * liegen als JSON, das im Browser gezeichnete Bild als PNG in der Media Library.
 */
#[Table('bm_stage_plots', key: 'band_id')]
#[WithoutIncrementing]
#[Fillable(['band_id', 'stage_width', 'stage_depth', 'items', 'notes', 'updated_by'])]
class StagePlot extends Model implements HasMedia
{
    /** @use HasFactory<StagePlotFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Neue Plots starten mit einer Bühne von 8 × 6 m.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'stage_width' => 8,
        'stage_depth' => 6,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::StagePlotImages->value)
            ->singleFile()
            ->useDisk('band-manager')
            ->acceptsMimeTypes(['image/png']);
    }
}
