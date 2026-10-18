<?php

namespace App\Actions\BandManager;

use App\Models\BandManager\Band;
use Illuminate\Support\Facades\DB;

/**
 * Löscht eine Band samt Dateien. Die übrigen Datensätze löscht die Datenbank per Fremdschlüssel mit –
 * Datensätze mit Media (Fotos, Logo, Galerie, Verträge & Rider) einzeln, damit die Media Library
 * die Dateien entfernt.
 */
class DeleteBandAction
{
    public function execute(Band $band): void
    {
        DB::transaction(function () use ($band): void {
            $band->teamMembers->each->delete();
            $band->galleryImages->each->delete();
            $band->documents->each->delete();
            $band->profile?->delete();
            $band->techRider?->delete();

            $band->delete();
        });
    }
}
