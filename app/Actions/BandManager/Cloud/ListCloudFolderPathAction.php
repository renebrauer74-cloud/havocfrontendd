<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFolder;
use Illuminate\Support\Collection;

/**
 * Ordnerpfad von der obersten Ebene bis zum Ordner selbst (für die Brotkrümel-Navigation).
 */
class ListCloudFolderPathAction
{
    /**
     * @return Collection<int, CloudFolder>
     */
    public function execute(CloudFolder $folder): Collection
    {
        $path = collect([$folder]);

        while ($folder->parent_id !== null && $folder = CloudFolder::query()->withTrashed()->find($folder->parent_id)) {
            $path->prepend($folder);
        }

        return $path;
    }
}
