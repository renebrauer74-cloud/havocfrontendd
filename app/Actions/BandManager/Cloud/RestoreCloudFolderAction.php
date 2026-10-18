<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use Illuminate\Support\Facades\DB;

/**
 * Holt einen Ordner mit allem zurück, was mit ihm zusammen gelöscht wurde. Was vorher schon einzeln
 * im Papierkorb lag, bleibt dort. Gibt es am Ziel schon einen Ordner mit dem Namen, wird ergänzt.
 */
class RestoreCloudFolderAction
{
    public function __construct(
        private ListCloudSubfolderIdsAction $listSubfolderIds,
        private ResolveCloudRestoreFolderAction $resolveRestoreFolder,
    ) {}

    public function execute(CloudFolder $folder): void
    {
        $folderIds = $this->listSubfolderIds->execute($folder, withTrashed: true);
        $deletedAt = $folder->deleted_at;
        $parentId = $this->resolveRestoreFolder->execute($folder->parent_id);

        DB::transaction(function () use ($folder, $folderIds, $deletedAt, $parentId): void {
            CloudFile::query()->onlyTrashed()->whereIn('folder_id', $folderIds)->where('deleted_at', $deletedAt)->update(['deleted_at' => null]);
            CloudFolder::query()->onlyTrashed()->whereIn('id', $folderIds)->where('deleted_at', $deletedAt)->whereKeyNot($folder->id)->update(['deleted_at' => null]);

            $folder->restore();
            $folder->update(['parent_id' => $parentId, 'name' => $this->freeName($folder, $parentId)]);
        });
    }

    private function freeName(CloudFolder $folder, ?int $parentId): string
    {
        $name = $folder->name;

        for ($number = 2; $this->nameTaken($folder, $parentId, $name); $number++) {
            $name = "{$folder->name} ({$number})";
        }

        return $name;
    }

    private function nameTaken(CloudFolder $folder, ?int $parentId, string $name): bool
    {
        return CloudFolder::query()
            ->where('band_id', $folder->band_id)
            ->where('parent_id', $parentId)
            ->where('name', $name)
            ->whereKeyNot($folder->id)
            ->exists();
    }
}
