<?php

namespace App\Models\BandManager;

use App\Enums\BandManager\CloudLinkTypeEnum;
use Database\Factories\BandManager\CloudLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Freigabe-Link für Externe auf einen Ordner (inkl. Unterordner) oder eine Datei im Cloud-Speicher.
 * Erreichbar ohne Login unter /band-manager/s/{token}.
 */
#[Table('bm_cloud_links')]
#[Fillable(['band_id', 'folder_id', 'file_id', 'token', 'type', 'message', 'allow_all_types', 'expires_at', 'last_used_at', 'created_by'])]
class CloudLink extends Model
{
    /** @use HasFactory<CloudLinkFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CloudLinkTypeEnum::class,
            'allow_all_types' => 'boolean',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Noch nicht abgelaufen.
     *
     * @param  Builder<CloudLink>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
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
    public function folder(): BelongsTo
    {
        return $this->belongsTo(CloudFolder::class, 'folder_id');
    }

    /**
     * @return BelongsTo<CloudFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(CloudFile::class, 'file_id');
    }

    /**
     * Dateien, die Externe über diesen (Upload-)Link hochgeladen haben.
     *
     * @return HasMany<CloudFile, $this>
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(CloudFile::class, 'link_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }
}
