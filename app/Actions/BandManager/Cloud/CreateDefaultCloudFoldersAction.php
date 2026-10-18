<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use Illuminate\Support\Facades\DB;

/**
 * Legt die vorgegebenen Ordner aus config('band-manager.cloud.default_folders') an – nur einmal pro Band,
 * danach gehören die Unterordner der Band (umbenennen, löschen, ergänzen).
 */
class CreateDefaultCloudFoldersAction
{
    public function execute(Band $band): void
    {
        if ($band->cloudFolders()->withTrashed()->where('is_preset', true)->exists()) {
            return;
        }

        DB::transaction(function () use ($band): void {
            foreach ((array) config('band-manager.cloud.default_folders') as $name => $children) {
                $folder = CloudFolder::query()->create(['band_id' => $band->id, 'name' => $name, 'is_preset' => true]);

                foreach ($children as $child) {
                    CloudFolder::query()->create(['band_id' => $band->id, 'parent_id' => $folder->id, 'name' => $child, 'is_preset' => true]);
                }
            }
        });
    }
}
