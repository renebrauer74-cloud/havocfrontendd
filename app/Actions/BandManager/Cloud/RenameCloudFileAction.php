<?php

namespace App\Actions\BandManager\Cloud;

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\CloudFile;

/**
 * Benennt eine Datei um – die Endung bleibt erhalten. Die Media Library verschiebt die Datei im Bucket mit.
 * Der Name wird bereinigt wie beim Upload (Leerzeichen, #, / und \ werden zu „-“).
 */
class RenameCloudFileAction
{
    public function execute(CloudFile $file, string $name): void
    {
        $media = $file->getFirstMedia(MediaCollectionEnum::CloudFiles->value);

        if ($media === null) {
            return;
        }

        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $base = $extension !== '' && str_ends_with(mb_strtolower($name), '.'.mb_strtolower($extension))
            ? mb_substr($name, 0, -mb_strlen($extension) - 1)
            : $name;

        $base = preg_replace('#\p{C}+#u', '', trim($base));
        $base = str_replace(['#', '/', '\\'], '-', (string) preg_replace('#\p{Z}#u', '-', (string) $base));
        $fileName = $extension !== '' ? $base.'.'.$extension : $base;

        if ($base === '' || $fileName === $media->file_name) {
            return;
        }

        $media->name = $base;
        $media->file_name = $fileName;
        $media->save();
    }
}
