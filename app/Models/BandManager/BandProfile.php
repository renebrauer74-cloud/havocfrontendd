<?php

namespace App\Models\BandManager;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Biografie (DE/EN), Social-Media-/Streaming-Links und Bandlogo – ein Datensatz pro Band.
 * Das Logo liegt unverändert (Transparenz bleibt) unter team/{band_id}/ auf dem Disk "band-manager".
 */
#[Table('bm_band_profiles', key: 'band_id')]
#[WithoutIncrementing]
#[Fillable(['band_id', 'bio_de', 'bio_en', 'links', 'logo', 'updated_by'])]
class BandProfile extends Model
{
    /** PNG zuerst – wegen transparentem Hintergrund die beste Wahl für Logos */
    public const LOGO_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'links' => 'array',
        ];
    }

    /**
     * @return array<string, array{label: string, icon: string, color: string, placeholder?: string, handle?: string}>
     */
    public static function platforms(): array
    {
        return config('band-manager.team.platforms', []);
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * Ausgefüllte Links in der Reihenfolge der Plattform-Liste.
     *
     * @return list<array{key: string, url: string, label: string, icon: string, color: string}>
     */
    public function linkList(): array
    {
        $links = [];

        foreach (self::platforms() as $key => $platform) {
            $url = $this->links[$key] ?? '';

            if (is_string($url) && $url !== '') {
                $links[] = ['key' => $key, 'url' => $url] + $platform;
            }
        }

        return $links;
    }

    /**
     * Link prüfen – "@name" wird bei Instagram, TikTok … zur vollständigen Adresse. null = ungültig.
     *
     * @param  array{handle?: string}  $platform
     */
    public static function normalizeUrl(string $value, array $platform): ?string
    {
        $url = trim($value);

        if (preg_match('/^@([A-Za-z0-9._-]{1,100})$/', $url, $matches)) {
            if (empty($platform['handle'])) {
                return null;
            }

            $url = sprintf($platform['handle'], $matches[1]);
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return str_contains((string) parse_url($url, PHP_URL_HOST), '.') ? mb_substr($url, 0, 255) : null;
    }

    /**
     * Für die Anzeige: "https://www.instagram.com/band/" → "instagram.com/band".
     */
    public static function display(string $url): string
    {
        return rtrim((string) preg_replace('#^https?://(www\.)?#i', '', $url), '/');
    }

    public function logoPath(): ?string
    {
        return $this->logo ? 'team/'.$this->band_id.'/'.basename($this->logo) : null;
    }

    public function logoUrl(bool $download = false): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return route('band-manager.team.logo', array_filter([
            'band' => $this->band_id,
            'v' => substr($this->logo, 5, 8),
            'download' => $download ? 1 : null,
        ]));
    }

    /**
     * Logo unverändert speichern. Liefert den Dateinamen.
     */
    public static function storeLogo(UploadedFile $file, int $bandId): string
    {
        $name = 'logo_'.bin2hex(random_bytes(12)).'.'.(self::LOGO_TYPES[$file->getMimeType()] ?? 'png');

        Storage::disk('band-manager')->putFileAs("team/{$bandId}", $file, $name);

        return $name;
    }

    public function deleteLogoFile(): void
    {
        if ($this->logoPath() !== null) {
            Storage::disk('band-manager')->delete($this->logoPath());
        }
    }
}
