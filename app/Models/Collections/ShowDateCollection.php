<?php

namespace App\Models\Collections;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Collection für Modelle mit Freitext-Datum in der Spalte `date` (z.B. "24.05.2026").
 *
 * @template TModel of Model
 *
 * @extends Collection<int, TModel>
 */
class ShowDateCollection extends Collection
{
    /**
     * Unterstützte Schreibweisen für das Freitext-Datum.
     * Zweistelliges Jahr zuerst – "Y" würde "26" sonst als Jahr 0026 lesen.
     */
    private const DATE_FORMATS = ['!d.m.y', '!d.m.Y', '!j.n.Y', '!Y-m-d'];

    /**
     * Chronologisch sortieren; nicht parsbare Daten (z.B. "12.-14. JUNI 2026") landen am Ende.
     */
    public function sortByShowDate(): static
    {
        return $this->sort(function (Model $a, Model $b): int {
            $timestampA = self::parseDate($a->date);
            $timestampB = self::parseDate($b->date);

            if ($timestampA === $timestampB) {
                return $a->getKey() <=> $b->getKey();
            }

            if ($timestampA === null) {
                return 1;
            }

            if ($timestampB === null) {
                return -1;
            }

            return $timestampA <=> $timestampB;
        })->values();
    }

    public static function parseDate(?string $date): ?int
    {
        $date = trim((string) $date);

        foreach (self::DATE_FORMATS as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $date);

            if ($parsed !== false && DateTimeImmutable::getLastErrors() === false) {
                return $parsed->getTimestamp();
            }
        }

        return null;
    }
}
