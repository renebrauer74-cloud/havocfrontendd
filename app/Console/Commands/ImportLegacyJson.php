<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('havoc:import-json
    {path? : Ordner mit den JSON-Dateien der alten Version (Standard: storage/data)}')]
#[Description('Übernimmt die JSON-Daten der alten Plain-PHP-Version einmalig in die Datenbank')]
class ImportLegacyJson extends Command
{
    /**
     * Reihenfolge wegen Fremdschlüsseln: artists vor tour_dates.
     *
     * Bilder werden nicht übernommen (sie liegen jetzt in der Media Library und werden neu hochgeladen),
     * daher fehlt auch "gallery" – ein Galerie-Eintrag ohne Bild ergibt keinen Sinn.
     *
     * @var list<string>
     */
    private const TABLES = ['artists', 'tour_dates', 'news', 'team', 'agency_shows', 'messages'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = rtrim($this->argument('path') ?? storage_path('data'), '/\\');

        foreach (self::TABLES as $table) {
            if (DB::table($table)->exists()) {
                $this->error("Abbruch: Tabelle '{$table}' ist nicht leer.");

                return self::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($path): void {
                foreach (self::TABLES as $table) {
                    $this->importTable($table, "{$path}/{$table}.json");
                }
            });
        } catch (Throwable $exception) {
            $this->error('Fehler, nichts übernommen: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * IDs bleiben erhalten, damit die Verknüpfungen über artist_id stimmen.
     */
    private function importTable(string $table, string $file): void
    {
        if (! is_file($file)) {
            $this->line("{$table}: keine JSON-Datei, übersprungen");

            return;
        }

        $rows = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR)['rows'] ?? [];
        $columns = array_flip(Schema::getColumnListing($table));

        foreach ($rows as $row) {
            $row = array_intersect_key($row, $columns);

            foreach (['created_at', 'updated_at'] as $timestamp) {
                if (! empty($row[$timestamp])) {
                    $row[$timestamp] = Carbon::parse($row[$timestamp])
                        ->setTimezone(config('app.timezone'))
                        ->format('Y-m-d H:i:s');
                }
            }

            DB::table($table)->insert($row);
        }

        $this->info("{$table}: ".count($rows).' Datensätze übernommen');
    }
}
