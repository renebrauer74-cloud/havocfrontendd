<?php

namespace App\Models\BandManager;

use App\Casts\TimeOfDayCast;
use App\Enums\BandManager\GigStatusEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use Carbon\CarbonImmutable;
use Database\Factories\BandManager\GigFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Auftritt einer Band inkl. Day Sheet. "date" bleibt ein Y-m-d-String (siehe Availability).
 * Bestätigte Gigs blockieren den Tag im Ampel-Kalender (BlockCalendarDayForGigAction).
 * Location Sheets der Veranstalter liegen in der Media Library und kommen hinten ans Day Sheet.
 */
#[Table('bm_gigs')]
#[Fillable([
    'band_id', 'date', 'title', 'venue', 'city', 'fee', 'status', 'notes', 'created_by', 'booked_by',
    // Day Sheet
    'venue_street', 'venue_zip', 'venue_country', 'parking_info',
    'time_get_in', 'time_soundcheck', 'time_dinner', 'time_doors', 'time_show', 'set_length', 'time_curfew', 'schedule_notes',
    'hotel_name', 'hotel_address', 'hotel_phone', 'hotel_checkin', 'hotel_checkout', 'hotel_notes',
    'catering', 'buyout_amount', 'catering_notes',
    'contact_venue_name', 'contact_venue_phone', 'contact_tech_name', 'contact_tech_phone',
    'guestlist', 'wifi', 'backline_info', 'merch_info', 'info_notes',
])]
class Gig extends Model implements HasMedia
{
    /** @use HasFactory<GigFactory> */
    use HasFactory, InteractsWithMedia;

    public const BOOKED_BY_AGENCY = 'agency';

    public const BOOKED_BY_BAND = 'band';

    /** Erlaubte Formate für Location Sheets */
    public const LOCATION_SHEET_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    /** Location Sheets als Bild – werden fürs Day Sheet verkleinert */
    public const LOCATION_SHEET_IMAGE_TYPES = ['image/jpeg', 'image/png'];

    /**
     * Uhrzeit-Felder des Day Sheets (als HH:MM, siehe TimeOfDayCast).
     */
    public const TIME_FIELDS = [
        'time_get_in', 'time_soundcheck', 'time_dinner', 'time_doors', 'time_show', 'time_curfew', 'hotel_checkin', 'hotel_checkout',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GigStatusEnum::class,
            'fee' => 'decimal:2',
            'buyout_amount' => 'decimal:2',
            'set_length' => 'integer',
        ] + array_fill_keys(self::TIME_FIELDS, TimeOfDayCast::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::LocationSheets->value)
            ->useDisk('band-manager')
            ->acceptsMimeTypes(self::LOCATION_SHEET_TYPES);
    }

    /**
     * Verkleinertes JPEG für Location Sheets als Bild (PDFs bleiben, wie sie sind).
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        if ($media === null || ! in_array($media->mime_type, self::LOCATION_SHEET_IMAGE_TYPES, true)) {
            return;
        }

        $size = (int) config('band-manager.gigs.location_sheets.image_px');

        $this->addMediaConversion(MediaConversionEnum::Preview->value)
            ->performOnCollections(MediaCollectionEnum::LocationSheets->value)
            ->fit(Fit::Max, $size, $size)
            ->format('jpg')
            ->nonQueued();
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * Mit diesem Gig verknüpfte Verträge.
     *
     * @return HasMany<Document, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Document::class)->where('type', Document::TYPE_CONTRACT);
    }

    /**
     * Reisegruppe: Bandmitglieder und Crew, die zu diesem Gig mitreisen.
     *
     * @return BelongsToMany<TeamMember, $this>
     */
    public function travelers(): BelongsToMany
    {
        return $this->belongsToMany(TeamMember::class, 'bm_gig_team_member');
    }

    /**
     * Von der Band selbst angelegt? Dann darf die Band alles sehen und bearbeiten.
     */
    public function isBandOwned(): bool
    {
        return $this->booked_by === self::BOOKED_BY_BAND;
    }

    public function isPast(): bool
    {
        return $this->date < CarbonImmutable::today()->format('Y-m-d');
    }

    /**
     * Ab heute ("heute" aus PHP, nicht vom Datenbankserver).
     *
     * @param  Builder<Gig>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('date', '>=', CarbonImmutable::today()->format('Y-m-d'));
    }

    /**
     * @param  Builder<Gig>  $query
     */
    #[Scope]
    protected function past(Builder $query): void
    {
        $query->where('date', '<', CarbonImmutable::today()->format('Y-m-d'));
    }

    /**
     * @param  Builder<Gig>  $query
     */
    #[Scope]
    protected function notCancelled(Builder $query): void
    {
        $query->where('status', '<>', GigStatusEnum::Cancelled);
    }
}
