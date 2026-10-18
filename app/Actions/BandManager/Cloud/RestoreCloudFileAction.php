<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFile;

/**
 * Holt eine Datei aus dem Papierkorb zurück – liegt ihr Ordner selbst im Papierkorb, in den nächsten
 * vorhandenen Ordner darüber.
 */
class RestoreCloudFileAction
{
    public function __construct(private ResolveCloudRestoreFolderAction $resolveRestoreFolder) {}

    public function execute(CloudFile $file): void
    {
        $file->folder_id = $this->resolveRestoreFolder->execute($file->folder_id);
        $file->restore();
    }
}
