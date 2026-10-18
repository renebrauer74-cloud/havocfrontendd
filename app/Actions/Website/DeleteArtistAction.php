<?php

namespace App\Actions\Website;

use App\Models\Artist;
use Illuminate\Support\Facades\DB;

/**
 * Löscht einen Künstler mit Tourdaten und Galerie. Die Galeriebilder einzeln, damit die Media Library
 * ihre Dateien mit entfernt.
 */
class DeleteArtistAction
{
    public function execute(Artist $artist): void
    {
        DB::transaction(function () use ($artist): void {
            $artist->galleryImages->each->delete();
            $artist->tourDates()->delete();
            $artist->delete();
        });
    }
}
