<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFolder;

/**
 * Wohin ein Eintrag aus dem Papierkorb zurückkommt: in seinen alten Ordner – liegt der selbst im
 * Papierkorb, in den nächsten noch vorhandenen Ordner darüber (notfalls in die oberste Ebene).
 */
class ResolveCloudRestoreFolderAction
{
    public function execute(?int $folderId): ?int
    {
        while ($folderId !== null) {
            $folder = CloudFolder::query()->withTrashed()->find($folderId);

            if ($folder === null) {
                return null;
            }

            if (! $folder->trashed()) {
                return $folder->id;
            }

            $folderId = $folder->parent_id;
        }

        return null;
    }
}
