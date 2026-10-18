<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use Illuminate\Support\Facades\DB;

/**
 * Legt einen Ordner samt Inhalt in den Papierkorb. Alles bekommt denselben Löschzeitpunkt –
 * daran erkennt RestoreCloudFolderAction, was gemeinsam zurückgeholt wird.
 */
class TrashCloudFolderAction
{
    public function __construct(private ListCloudSubfolderIdsAction $listSubfolderIds) {}

    public function execute(CloudFolder $folder): void
    {
        $folderIds = $this->listSubfolderIds->execute($folder);
        $deletedAt = now()->startOfSecond();

        DB::transaction(function () use ($folderIds, $deletedAt): void {
            CloudFile::query()->whereIn('folder_id', $folderIds)->update(['deleted_at' => $deletedAt]);
            CloudFolder::query()->whereIn('id', $folderIds)->update(['deleted_at' => $deletedAt]);
        });
    }
}
