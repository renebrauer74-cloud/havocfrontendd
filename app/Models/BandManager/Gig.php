<?php

namespace App\Models\BandManager;

use Carbon\CarbonImmutable;
use Database\Factories\BandManager\GigFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * Auftritt einer Band inkl. Day Sheet. "date" bleibt ein Y-m-d-String (siehe Availability).
 * Bestätigte Gigs blockieren den Tag im Ampel-Kalender automatisch.
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
class Gig extends Model
{
    /** @use HasFactory<GigFactory> */
    use HasFactory;

    public const BOOKED_BY_AGENCY = 'agency';

    public const BOOKED_BY_BAND = 'band';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Uhrzeit-Felder des Day Sheets (werden als HH:MM angezeigt).
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
            'fee' => 'decimal:2',
            'buyout_amount' => 'decimal:2',
            'set_length' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (Gig $gig) => $gig->blockCalendarDay());
    }

    /**
     * @return array<string, array{label: string, color: string}>
     */
    public static function statuses(): array
    {
        return config('band-manager.gigs.statuses', []);
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
     * @return array{label: string, color: string}
     */
    public function statusDefinition(): array
    {
        return self::statuses()[$this->status] ?? ['label' => $this->status, 'color' => '#6b7280'];
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
     * Uhrzeit als HH:MM (MariaDB liefert HH:MM:SS) – leer, wenn nicht gesetzt.
     */
    public function time(string $field): string
    {
        return $this->{$field} ? substr((string) $this->{$field}, 0, 5) : '';
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
        $query->where('status', '<>', self::STATUS_CANCELLED);
    }

    /**
     * Bestätigter Gig → Tag im Ampel-Kalender auf "Rot" (config band-manager.gigs.block_*).
     */
    public function blockCalendarDay(): void
    {
        if (! config('band-manager.gigs.block_availability') || $this->status !== self::STATUS_CONFIRMED) {
            return;
        }

        $day = CarbonImmutable::createFromFormat('!Y-m-d', $this->date);

        Availability::setRange($this->band, $day, $day, (string) config('band-manager.gigs.block_status', 'red'), 'Gig: '.$this->title, Auth::guard('band-manager')->id());
    }
}
