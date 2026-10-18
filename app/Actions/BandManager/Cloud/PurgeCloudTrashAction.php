<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use Carbon\CarbonInterface;

/**
 * Leert den Papierkorb endgültig – für eine Band („Papierkorb leeren“) oder alles, was vor einem
 * Zeitpunkt gelöscht wurde (Befehl band-manager:cloud-purge-trash).
 */
class PurgeCloudTrashAction
{
    public function __construct(private DeleteCloudFolderAction $deleteFolder) {}

    /**
     * @return int Anzahl endgültig gelöschter Dateien
     */
    public function execute(?Band $band = null, ?CarbonInterface $trashedBefore = null): int
    {
        $files = 0;

        $folders = CloudFolder::query()->onlyTrashed()
            ->when($band, fn ($query) => $query->where('band_id', $band->id))
            ->when($trashedBefore, fn ($query) => $query->where('deleted_at', '<', $trashedBefore))
            ->orderBy('id')
            ->get();

        foreach ($folders as $folder) {
            // Unterordner sind mit einem Ordner darüber evtl. schon weg
            if (CloudFolder::query()->withTrashed()->whereKey($folder->id)->exists()) {
                $files += $this->deleteFolder->execute($folder);
            }
        }

        CloudFile::query()->onlyTrashed()
            ->when($band, fn ($query) => $query->where('band_id', $band->id))
            ->when($trashedBefore, fn ($query) => $query->where('deleted_at', '<', $trashedBefore))
            ->with('media')
            ->get()
            ->each(function (CloudFile $file) use (&$files): void {
                $file->forceDelete();
                $files++;
            });

        return $files;
    }
}
