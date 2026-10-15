<?php

namespace App\Models\BandManager;

use App\Services\BandManager\Images;
use Database\Factories\BandManager\GalleryImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Foto in der Bildergalerie einer Band. Original + Vorschau (max. 1600 px, JPEG) liegen
 * auf dem Disk "band-manager" unter gallery/{band_id}/.
 */
#[Table('bm_gallery_images')]
#[Fillable([
    'band_id', 'category', 'caption', 'credit', 'is_main', 'sort_order',
    'file', 'preview', 'original_name', 'mime', 'width', 'height', 'size_bytes', 'uploaded_by',
])]
class GalleryImage extends Model
{
    /** @use HasFactory<GalleryImageFactory> */
    use HasFactory;

    public const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** Das Hauptbild ist immer ein Bandfoto */
    public const MAIN_CATEGORY = 'band';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(fn (GalleryImage $image) => $image->deleteFiles());
    }

    public static function maxImages(): int
    {
        return max(1, (int) config('band-manager.gallery.max_images'));
    }

    /**
     * Max. Dateigröße pro Bild in KB – berücksichtigt auch upload_max_filesize des Servers.
     */
    public static function maxKilobytes(): int
    {
        $own = (int) config('band-manager.gallery.max_photo_mb') * 1024;
        $ini = (int) floor(app(Images::class)->iniBytes((string) ini_get('upload_max_filesize')) / 1024);

        return $ini > 0 ? min($own, $ini) : $own;
    }

    /**
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return config('band-manager.gallery.categories', []);
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * Hauptbild zuerst, dann in Upload-Reihenfolge.
     *
     * @param  Builder<GalleryImage>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderByDesc('is_main')->orderBy('sort_order')->orderBy('id');
    }

    public static function mainFor(Band $band): ?self
    {
        return $band->galleryImages()->where('is_main', true)->first();
    }

    public function categoryLabel(): string
    {
        return self::categories()[$this->category] ?? 'Sonstiges';
    }

    public function sizeLabel(): string
    {
        return $this->size_bytes >= 1048576
            ? number_format($this->size_bytes / 1048576, 1, ',', '.').' MB'
            : max(1, (int) round($this->size_bytes / 1024)).' KB';
    }

    public function url(bool $original = false, bool $download = false): string
    {
        return route('band-manager.gallery.image', array_filter([
            'image' => $this,
            'v' => substr($this->file, 0, 8),
            'size' => $original ? 'original' : null,
            'download' => $download ? 1 : null,
        ]));
    }

    /**
     * Genau ein Hauptbild pro Band – es wird automatisch zum Bandfoto.
     */
    public function makeMain(): void
    {
        DB::transaction(function (): void {
            static::query()->where('band_id', $this->band_id)->where('is_main', true)->update(['is_main' => false]);
            $this->update(['is_main' => true, 'category' => self::MAIN_CATEGORY]);
        });
    }

    /**
     * Pfad auf dem Disk – Vorschau, falls vorhanden, sonst Original.
     */
    public function storagePath(bool $preview = true): string
    {
        $directory = 'gallery/'.$this->band_id.'/';

        if ($preview && $this->preview && Storage::disk('band-manager')->exists($directory.$this->preview)) {
            return $directory.basename($this->preview);
        }

        return $directory.basename($this->file);
    }

    public function isPreviewPath(string $path): bool
    {
        return $this->preview !== null && basename($path) === $this->preview;
    }

    /**
     * Bild für Dompdf: data-URI plus Abmessungen (nur JPEG/PNG bis 6 MB).
     *
     * @return array{src: string, w: int, h: int}|null
     */
    public function pdfSource(): ?array
    {
        $disk = Storage::disk('band-manager');
        $path = $this->storagePath();

        if (! $disk->exists($path)) {
            return null;
        }

        $mime = $this->isPreviewPath($path) ? 'image/jpeg' : $this->mime;

        if (! in_array($mime, ['image/jpeg', 'image/png'], true) || $disk->size($path) > 6 * 1048576) {
            return null;
        }

        [$width, $height] = app(Images::class)->dimensions($disk->path($path));

        return [
            'src' => 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path)),
            'w' => (int) $width,
            'h' => (int) $height,
        ];
    }

    /**
     * Original speichern und Vorschau erzeugen. Liefert die Datei-Felder für das Model.
     *
     * @return array{file: string, preview: ?string, width: ?int, height: ?int, mime: string, size_bytes: int, original_name: string}
     */
    public static function storeUpload(UploadedFile $upload, int $bandId): array
    {
        $disk = Storage::disk('band-manager');
        $images = app(Images::class);
        $mime = (string) $upload->getMimeType();
        $name = bin2hex(random_bytes(16));
        $file = $name.'.'.(self::TYPES[$mime] ?? 'jpg');

        $disk->putFileAs("gallery/{$bandId}", $upload, $file);

        // Große Fotos: Vorschau-Erstellung darf etwas länger dauern
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $original = $disk->path("gallery/{$bandId}/{$file}");
        [$width, $height] = $images->dimensions($original);
        $preview = $images->resizeToJpeg(
            $original,
            $mime,
            $disk->path("gallery/{$bandId}/{$name}_p.jpg"),
            max(400, (int) config('band-manager.gallery.preview_px')),
            max(1, (int) config('band-manager.gallery.max_preview_mp')) * 1_000_000,
        ) ? "{$name}_p.jpg" : null;

        return [
            'file' => $file,
            'preview' => $preview,
            'width' => $width,
            'height' => $height,
            'mime' => $mime,
            'size_bytes' => (int) $upload->getSize(),
            'original_name' => mb_substr(basename(str_replace('\\', '/', $upload->getClientOriginalName())), 0, 255) ?: 'bild',
        ];
    }

    public function deleteFiles(): void
    {
        $directory = 'gallery/'.$this->band_id.'/';

        Storage::disk('band-manager')->delete(array_filter([
            $this->file ? $directory.basename($this->file) : null,
            $this->preview ? $directory.basename($this->preview) : null,
        ]));
    }
}
