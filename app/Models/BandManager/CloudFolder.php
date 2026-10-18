<?php

namespace App\Models\BandManager;

use Database\Factories\BandManager\CloudFolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ordner im Cloud-Speicher einer Band. Ohne parent_id liegt er in der obersten Ebene.
 * Vorgegebene Hauptordner (is_preset) kann die Band nicht umbenennen, verschieben oder löschen.
 */
#[Table('bm_cloud_folders')]
#[Fillable(['band_id', 'parent_id', 'name', 'is_preset', 'created_by'])]
class CloudFolder extends Model
{
    /** @use HasFactory<CloudFolderFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_preset' => 'boolean',
        ];
    }

    /**
     * Vorgegebene Ordner zuerst (in der Reihenfolge der Config), danach eigene Ordner nach Name.
     * „case when is_preset“ ohne Vergleich mit 1 – PostgreSQL (Live) kennt kein boolean = integer.
     *
     * @param  Builder<CloudFolder>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderByDesc('is_preset')
            ->orderByRaw('case when is_preset then id else 0 end')
            ->orderBy('name');
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * @return BelongsTo<CloudFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<CloudFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<CloudFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(CloudFile::class, 'folder_id');
    }

    public function isLocked(): bool
    {
        return $this->is_preset && $this->parent_id === null;
    }
}
