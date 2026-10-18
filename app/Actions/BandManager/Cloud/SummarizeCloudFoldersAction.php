<?php

namespace App\Actions\BandManager\Cloud;

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Inhalt je Ordner einer Band für die Ordnerlisten: direkte Unterordner sowie Dateien und Größe
 * inklusive aller Unterordner (ohne Papierkorb). Zwei Abfragen für die ganze Band, summiert wird in PHP.
 */
class SummarizeCloudFoldersAction
{
    /**
     * @return array<int, array{folders: int, files: int, bytes: int}>
     */
    public function execute(Band $band): array
    {
        $parents = $band->cloudFolders()->pluck('parent_id', 'id')->all();
        $summary = array_map(fn (): array => ['folders' => 0, 'files' => 0, 'bytes' => 0], $parents);

        foreach ($parents as $parentId) {
            if ($parentId !== null && isset($summary[$parentId])) {
                $summary[$parentId]['folders']++;
            }
        }

        $files = CloudFile::query()->where('band_id', $band->id)->whereNotNull('folder_id');
        $folderOfFile = (clone $files)->pluck('folder_id', 'id')->all();
        $sizes = Media::query()
            ->where('model_type', (new CloudFile)->getMorphClass())
            ->where('collection_name', MediaCollectionEnum::CloudFiles->value)
            ->whereIn('model_id', $files->select('id'))
            ->pluck('size', 'model_id')
            ->all();

        foreach ($folderOfFile as $fileId => $folderId) {
            // Datei zählt in ihrem Ordner und in allen Ordnern darüber
            for ($id = $folderId; $id !== null && isset($summary[$id]); $id = $parents[$id]) {
                $summary[$id]['files']++;
                $summary[$id]['bytes'] += (int) ($sizes[$fileId] ?? 0);
            }
        }

        return $summary;
    }
}
