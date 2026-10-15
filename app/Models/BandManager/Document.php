<?php

namespace App\Models\BandManager;

use Database\Factories\BandManager\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Vertrag oder Rider (PDF). Dateien liegen auf dem Disk "band-manager" unter documents/{band_id}/.
 */
#[Table('bm_documents')]
#[Fillable(['band_id', 'gig_id', 'type', 'category', 'title', 'original_name', 'stored_name', 'size_bytes', 'uploaded_by'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

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
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (Document $document) => Storage::disk('band-manager')->delete($document->storagePath()));
    }

    /**
     * @return array<string, string>
     */
    public static function riderCategories(): array
    {
        return config('band-manager.documents.rider_categories', []);
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

    public function categoryLabel(): string
    {
        return $this->category ? (self::riderCategories()[$this->category] ?? $this->category) : '–';
    }

    public function sizeLabel(): string
    {
        return $this->size_bytes >= 1048576
            ? number_format($this->size_bytes / 1048576, 1, ',', '.').' MB'
            : max(1, (int) round($this->size_bytes / 1024)).' KB';
    }

    public function storagePath(): string
    {
        return 'documents/'.$this->band_id.'/'.basename($this->stored_name);
    }
}
