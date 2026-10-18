<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Einträge im Papierkorb einer Band – nur die „obersten“: Was mit einem Ordner zusammen gelöscht wurde,
 * steckt in diesem Ordner und wird mit ihm wiederhergestellt.
 */
class ListCloudTrashAction
{
    /**
     * @return array{folders: Collection<int, CloudFolder>, files: Collection<int, CloudFile>, parents: Collection<int, CloudFolder>}
     */
    public function execute(Band $band): array
    {
        $folders = $band->cloudFolders()->onlyTrashed()->latest('deleted_at')->get();
        $files = $band->cloudFiles()->onlyTrashed()->with(['media', 'uploader'])->latest('deleted_at')->get();

        $parents = CloudFolder::query()->withTrashed()
            ->whereIn('id', $folders->pluck('parent_id')->merge($files->pluck('folder_id'))->filter()->unique())
            ->get()
            ->keyBy('id');

        $deletedWithParent = fn (?int $parentId, CloudFolder|CloudFile $item): bool => $parentId !== null
            && $parents->get($parentId)?->deleted_at?->equalTo($item->deleted_at) === true;

        return [
            'folders' => $folders->reject(fn (CloudFolder $folder): bool => $deletedWithParent($folder->parent_id, $folder))->values(),
            'files' => $files->reject(fn (CloudFile $file): bool => $deletedWithParent($file->folder_id, $file))->values(),
            'parents' => $parents,
        ];
    }
}
