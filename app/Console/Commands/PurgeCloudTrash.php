<?php

namespace App\Console\Commands;

use App\Actions\BandManager\Cloud\PurgeCloudTrashAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('band-manager:cloud-purge-trash')]
#[Description('Löscht Cloud-Dateien und -Ordner endgültig, die länger als config(band-manager.cloud.trash_days) im Papierkorb liegen')]
class PurgeCloudTrash extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PurgeCloudTrashAction $purgeTrash): int
    {
        $days = (int) config('band-manager.cloud.trash_days');
        $count = $purgeTrash->execute(trashedBefore: now()->subDays($days));

        $this->info("{$count} Datei(en) aus dem Papierkorb endgültig gelöscht (älter als {$days} Tage).");

        return self::SUCCESS;
    }
}
