<?php

namespace App\Actions\BandManager\Cloud;

use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\CloudLink;

/**
 * Darf ein Freigabe-Link (noch) genutzt werden – und gehört der Ordner bzw. die Datei zu dem, was er freigibt?
 * Abgelaufene Links und Ziele im Papierkorb sperren den Link.
 */
class CheckCloudLinkAccessAction
{
    public function __construct(private ListCloudSubfolderIdsAction $listSubfolderIds) {}

    public function execute(CloudLink $link, CloudFolder|CloudFile|null $item = null): bool
    {
        if ($link->isExpired()) {
            return false;
        }

        if ($link->file_id !== null) {
            return $link->file !== null && ($item === null || ($item instanceof CloudFile && $item->is($link->file)));
        }

        if ($link->folder === null) {
            return false;
        }

        if ($item === null) {
            return true;
        }

        $folderIds = $this->listSubfolderIds->execute($link->folder);

        return in_array($item instanceof CloudFolder ? $item->id : $item->folder_id, $folderIds, true);
    }
}
