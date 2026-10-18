<?php

namespace App\Actions\MediaLibrary;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Zieht Media-Dateien inkl. Conversions auf eine andere Disk um – IDs, UUIDs und Pfade bleiben gleich.
 * Erst kopieren, dann den Datensatz umstellen, dann die Quelle löschen: Bricht es ab, ist nichts verloren.
 */
class MoveMediaToDiskAction
{
    /**
     * @param  list<string>  $collections  leer = alle Collections
     * @return int Anzahl verschobener Media
     */
    public function execute(string $from, string $to, array $collections = []): int
    {
        $source = Storage::disk($from);
        $target = Storage::disk($to);
        $moved = 0;

        $query = Media::query()
            ->where('disk', $from)
            ->when($collections !== [], fn ($query) => $query->whereIn('collection_name', $collections));

        foreach ($query->lazyById() as $media) {
            $directory = dirname($media->getPathRelativeToRoot());

            foreach ($source->allFiles($directory) as $file) {
                $target->writeStream($file, $source->readStream($file));
            }

            $media->disk = $to;
            $media->conversions_disk = $media->conversions_disk === $from ? $to : $media->conversions_disk;
            $media->save();

            $source->deleteDirectory($directory);
            $moved++;
        }

        return $moved;
    }
}
