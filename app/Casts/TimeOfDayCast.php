<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Uhrzeit als HH:MM – MariaDB/MySQL liefern HH:MM:SS.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class TimeOfDayCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return filled($value) ? substr((string) $value, 0, 5) : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return filled($value) ? substr((string) $value, 0, 5) : null;
    }
}
