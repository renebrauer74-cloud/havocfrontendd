<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;

/**
 * Löscht einen Ordner endgültig: zuerst alle Dateien darin (einzeln, damit die Media Library sie aus dem
 * Bucket entfernt), dann den Ordner – die Unterordner löscht die Datenbank per Fremdschlüssel mit.
 */
class DeleteCloudFolderAction
{
    public function __construct(private ListCloudSubfolderIdsAction $listSubfolderIds) {}

    /**
     * @return int Anzahl gelöschter Dateien
     */
    public function execute(CloudFolder $folder): int
    {
        $folderIds = $this->listSubfolderIds->execute($folder, withTrashed: true);
        $files = CloudFile::query()->withTrashed()->whereIn('folder_id', $folderIds)->with('media')->get();

        $files->each->forceDelete();
        $folder->forceDelete();

        return $files->count();
    }
}
