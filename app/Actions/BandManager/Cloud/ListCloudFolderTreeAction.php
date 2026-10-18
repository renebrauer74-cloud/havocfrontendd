<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use Illuminate\Support\Collection;

/**
 * Alle Ordner einer Band als eingerückte Liste (Ziele für „Verschieben“), vorgegebene Hauptordner zuerst.
 */
class ListCloudFolderTreeAction
{
    /**
     * @return list<array{folder: CloudFolder, depth: int}>
     */
    public function execute(Band $band): array
    {
        $byParent = $band->cloudFolders()->ordered()->get()
            ->groupBy(fn (CloudFolder $folder): int => (int) $folder->parent_id);

        return $this->branch($byParent, 0, 0);
    }

    /**
     * @param  Collection<int, Collection<int, CloudFolder>>  $byParent
     * @return list<array{folder: CloudFolder, depth: int}>
     */
    private function branch(Collection $byParent, int $parentId, int $depth): array
    {
        $rows = [];

        foreach ($byParent->get($parentId, collect()) as $folder) {
            $rows[] = ['folder' => $folder, 'depth' => $depth];
            array_push($rows, ...$this->branch($byParent, $folder->id, $depth + 1));
        }

        return $rows;
    }
}
