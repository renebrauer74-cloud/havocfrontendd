<?php

namespace App\Models\BandManager;

use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use Database\Factories\BandManager\CloudFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Datei im Cloud-Speicher einer Band (ohne folder_id in der obersten Ebene). Die Datei selbst liegt in der
 * Media Library unter cloud/<band-id>/<uuid>/datei (App\Services\MediaLibrary\CloudPathGenerator).
 * Im Papierkorb (Soft Delete) bleibt die Datei erhalten und zählt weiter zum Speicherplatz.
 */
#[Table('bm_cloud_files')]
#[Fillable(['band_id', 'folder_id', 'uploaded_by', 'link_id', 'uploader_name'])]
class CloudFile extends Model implements HasMedia
{
    /** @use HasFactory<CloudFileFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /** Bildformate, für die eine Vorschau erzeugt wird */
    public const THUMBNAIL_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * Formate, die der Browser direkt anzeigen darf – alles andere (z. B. HTML, SVG) nur als Download,
     * damit hochgeladene Dateien kein Skript auf unserer Domain ausführen.
     */
    public const INLINE_TYPES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf', 'text/plain',
        'audio/mpeg', 'audio/mp4', 'audio/aac', 'audio/wav', 'audio/x-wav', 'audio/flac', 'audio/x-flac', 'audio/ogg',
        'video/mp4', 'video/webm', 'video/quicktime',
    ];

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
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Upload-Link, über den ein Externer die Datei hochgeladen hat (Name in uploader_name).
     *
     * @return BelongsTo<CloudLink, $this>
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(CloudLink::class, 'link_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::CloudFiles->value)
            ->singleFile()
            ->useDisk('band-manager');
    }

    /**
     * Kleine Vorschau nur für gängige Bilder bis max_megapixels (Breite/Höhe als Custom Properties) –
     * größere Bilder bräuchten beim Verkleinern zu viel RAM.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media === null || ! in_array($media->mime_type, self::THUMBNAIL_TYPES, true)) {
            return;
        }

        $pixels = (int) $media->getCustomProperty('width') * (int) $media->getCustomProperty('height');

        if ($pixels === 0 || $pixels > (int) config('band-manager.cloud.max_megapixels') * 1_000_000) {
            return;
        }

        $size = (int) config('band-manager.cloud.thumbnail_px');

        $this->addMediaConversion(MediaConversionEnum::Thumbnail->value)
            ->fit(Fit::Crop, $size, $size)
            ->format('jpg')
            ->nonQueued();
    }
}
