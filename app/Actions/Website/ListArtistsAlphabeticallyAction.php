<?php

namespace App\Actions\Website;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Collection;

/**
 * Alle Künstler, natürlich und ohne Groß-/Kleinschreibung nach Namen sortiert („Band 2“ vor „Band 10“).
 */
class ListArtistsAlphabeticallyAction
{
    /**
     * @return Collection<int, Artist>
     */
    public function execute(): Collection
    {
        return Artist::query()->get()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }
}
