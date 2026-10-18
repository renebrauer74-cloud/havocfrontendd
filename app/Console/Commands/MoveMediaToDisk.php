<?php

namespace App\Console\Commands;

use App\Actions\MediaLibrary\MoveMediaToDiskAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('havoc:move-media
    {from : Disk, auf dem die Dateien jetzt liegen (z. B. band-manager)}
    {to : Ziel-Disk (z. B. band-manager-private)}
    {--collection=* : Nur diese Collections (Standard: alle)}')]
#[Description('Zieht Media-Dateien inkl. Vorschauen auf eine andere Disk um – IDs, UUIDs und Pfade bleiben gleich')]
class MoveMediaToDisk extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(MoveMediaToDiskAction $moveMedia): int
    {
        $from = (string) $this->argument('from');
        $to = (string) $this->argument('to');

        foreach ([$from, $to] as $disk) {
            if (config("filesystems.disks.{$disk}") === null) {
                $this->error("Disk '{$disk}' ist nicht konfiguriert.");

                return self::FAILURE;
            }
        }

        if ($from === $to) {
            $this->error('Quelle und Ziel sind dieselbe Disk.');

            return self::FAILURE;
        }

        $moved = $moveMedia->execute($from, $to, $this->option('collection'));

        $this->info("{$moved} Datei(en) von '{$from}' nach '{$to}' verschoben.");

        return self::SUCCESS;
    }
}
