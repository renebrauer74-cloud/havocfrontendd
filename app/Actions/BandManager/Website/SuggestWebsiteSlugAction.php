<?php

namespace App\Actions\BandManager\Website;

use App\Models\BandManager\Band;
use App\Models\BandManager\BandWebsite;
use Illuminate\Support\Str;

/**
 * Vorschlag für die Adresse einer neuen Website: Bandname als Slug ("Iron Kittens" → "iron-kittens"),
 * bei Kollision mit Zähler ("iron-kittens-2").
 */
class SuggestWebsiteSlugAction
{
    public function execute(Band $band): string
    {
        $base = Str::limit(Str::slug($band->name), 50, '') ?: 'band-'.$band->id;
        $slug = $base;
        $counter = 2;

        while (BandWebsite::query()->where('slug', $slug)->whereKeyNot($band->id)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
