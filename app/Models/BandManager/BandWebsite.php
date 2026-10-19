<?php

namespace App\Models\BandManager;

use App\Enums\BandManager\WebsiteFontEnum;
use App\Enums\BandManager\WebsiteHeroStyleEnum;
use App\Enums\BandManager\WebsiteLanguageEnum;
use App\Enums\BandManager\WebsiteThemeEnum;
use Database\Factories\BandManager\BandWebsiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Website einer Band (Website Builder → Website Creator) – ein Datensatz pro Band, öffentlich unter /sites/{slug}.
 * Gespeichert werden nur Design, Fotoauswahl und Abschnitte (Reihenfolge + ein/aus als JSON) –
 * Biografie, Logo, Fotos, Links, Mitglieder und Shows kommen bei jedem Aufruf frisch aus dem Band-Manager.
 */
#[Table('bm_band_websites', key: 'band_id')]
#[WithoutIncrementing]
#[Fillable([
    'band_id', 'slug', 'is_published', 'published_at',
    'theme', 'font', 'hero_style', 'accent_color', 'effect_grain', 'effect_animations',
    'language', 'tagline', 'about_text', 'hero_image_id', 'gallery_image_ids', 'sections',
    'spotify_url', 'youtube_url', 'contact_email', 'show_agency_booking', 'imprint', 'updated_by',
])]
class BandWebsite extends Model
{
    /** @use HasFactory<BandWebsiteFactory> */
    use HasFactory;

    /**
     * Neue Websites starten im Theme „Noir“.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_published' => false,
        'theme' => 'noir',
        'font' => 'condensed',
        'hero_style' => 'fullscreen',
        'effect_grain' => true,
        'effect_animations' => true,
        'language' => 'de',
        'show_agency_booking' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'theme' => WebsiteThemeEnum::class,
            'font' => WebsiteFontEnum::class,
            'hero_style' => WebsiteHeroStyleEnum::class,
            'language' => WebsiteLanguageEnum::class,
            'effect_grain' => 'boolean',
            'effect_animations' => 'boolean',
            'show_agency_booking' => 'boolean',
            'gallery_image_ids' => 'array',
            'sections' => 'array',
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
     * Titelfoto aus der Galerie – ohne Auswahl nimmt die Website das Hauptbild.
     *
     * @return BelongsTo<GalleryImage, $this>
     */
    public function heroImage(): BelongsTo
    {
        return $this->belongsTo(GalleryImage::class, 'hero_image_id');
    }
}
