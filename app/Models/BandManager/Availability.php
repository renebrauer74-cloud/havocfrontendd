<?php

namespace App\Models\BandManager;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Database\Factories\BandManager\AvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Ampel-Status einer Band an einem Tag. "date" bleibt bewusst ein Y-m-d-String,
 * damit Vergleiche auf SQLite und MariaDB gleich funktionieren.
 */
#[Table('bm_availabilities')]
#[Fillable(['band_id', 'date', 'status', 'note', 'updated_by'])]
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    /**
     * @return array<string, array{label: string, color: string}>
     */
    public static function statuses(): array
    {
        return config('band-manager.availability.statuses', []);
    }

    /**
     * @return BelongsTo<Band, $this>
     */
    public function band(): BelongsTo
    {
        return $this->belongsTo(Band::class);
    }

    /**
     * @return array{label: string, color: string}
     */
    public function statusDefinition(): array
    {
        return self::statuses()[$this->status] ?? ['label' => $this->status, 'color' => '#9ca3af'];
    }

    /**
     * Setzt den Status für alle Tage von $from bis $to (beide inklusiv). Liefert die Anzahl der Tage.
     */
    public static function setRange(Band $band, CarbonImmutable $from, CarbonImmutable $to, string $status, ?string $note, ?int $userId): int
    {
        $rows = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            $rows[] = [
                'band_id' => $band->id,
                'date' => $day->format('Y-m-d'),
                'status' => $status,
                'note' => $note,
                'updated_by' => $userId,
            ];
        }

        DB::transaction(fn () => static::query()->upsert($rows, ['band_id', 'date'], ['status', 'note', 'updated_by']));

        return count($rows);
    }

    /**
     * Löscht alle Einträge von $from bis $to (beide inklusiv). Liefert die Anzahl gelöschter Tage.
     */
    public static function clearRange(Band $band, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return static::query()
            ->where('band_id', $band->id)
            ->whereBetween('date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->delete();
    }

    /**
     * Anzahl Tage pro Band und Status im Zeitraum ab heute: [band_id => [status => anzahl]].
     *
     * @return array<int, array<string, int>>
     */
    public static function summary(int $days): array
    {
        $today = CarbonImmutable::today();

        $summary = [];

        static::query()
            ->selectRaw('band_id, status, COUNT(*) AS days')
            ->whereBetween('date', [$today->format('Y-m-d'), $today->addDays($days)->format('Y-m-d')])
            ->groupBy('band_id', 'status')
            ->get()
            ->each(function (Availability $row) use (&$summary): void {
                $summary[$row->band_id][$row->status] = (int) $row->days;
            });

        return $summary;
    }
}
