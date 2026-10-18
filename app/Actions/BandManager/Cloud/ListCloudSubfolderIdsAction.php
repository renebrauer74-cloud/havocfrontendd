<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFolder;

/**
 * IDs eines Ordners und aller Ordner darunter (beliebig tief), auf Wunsch inklusive Papierkorb.
 */
class ListCloudSubfolderIdsAction
{
    /**
     * @return list<int>
     */
    public function execute(CloudFolder $folder, bool $withTrashed = false): array
    {
        $ids = [$folder->id];
        $level = [$folder->id];

        while ($level !== []) {
            $level = CloudFolder::query()
                ->when($withTrashed, fn ($query) => $query->withTrashed())
                ->whereIn('parent_id', $level)
                ->pluck('id')
                ->all();

            array_push($ids, ...$level);
        }

        return $ids;
    }
}
