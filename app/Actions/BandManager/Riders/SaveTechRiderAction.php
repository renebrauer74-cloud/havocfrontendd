<?php

namespace App\Actions\BandManager\Riders;

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\TechRider;
use App\Models\BandManager\User;
use App\Services\BandManager\Pdf;
use Illuminate\Http\UploadedFile;

/**
 * Speichert den Tech Rider einer Band. Leere Zeilen der Listen fallen weg, die Reihenfolge bleibt erhalten.
 * Ein neuer Bühnenplan ersetzt den alten erst, wenn er sicher gespeichert ist (singleFile).
 */
class SaveTechRiderAction
{
    /**
     * Spalten je Liste – die erste Gruppe muss ausgefüllt sein, damit eine Zeile gespeichert wird.
     *
     * @var array<string, array{required: list<string>, text: list<string>, flags: list<string>}>
     */
    private const array LISTS = [
        'line_up' => ['required' => ['name', 'instrument'], 'text' => ['name', 'instrument'], 'flags' => []],
        'inputs' => ['required' => ['source', 'mic', 'note'], 'text' => ['source', 'mic', 'stand', 'note'], 'flags' => ['phantom']],
        'monitors' => ['required' => ['who', 'note'], 'text' => ['who', 'type', 'note'], 'flags' => []],
        'backline' => ['required' => ['item', 'note'], 'text' => ['item', 'provider', 'note'], 'flags' => []],
        'custom_sections' => ['required' => ['title', 'content'], 'text' => ['title', 'content'], 'flags' => []],
    ];

    public function __construct(private Pdf $pdf) {}

    /**
     * @param  array<string, mixed>  $data  validierte Eingaben aus TechRiderRequest
     */
    public function execute(Band $band, array $data, User $user, ?UploadedFile $stagePlot = null, bool $removeStagePlot = false): TechRider
    {
        $rider = $band->techRider;

        $rider->fill([
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'brings_foh_engineer' => (bool) ($data['brings_foh_engineer'] ?? false),
            'brings_monitor_engineer' => (bool) ($data['brings_monitor_engineer'] ?? false),
            'setup_minutes' => $data['setup_minutes'] ?? null,
            'soundcheck_minutes' => $data['soundcheck_minutes'] ?? null,
            'show_minutes' => $data['show_minutes'] ?? null,
            'teardown_minutes' => $data['teardown_minutes'] ?? null,
            'pa_notes' => $data['pa_notes'] ?? null,
            'monitor_notes' => $data['monitor_notes'] ?? null,
            'stage_width' => $data['stage_width'] ?? null,
            'stage_depth' => $data['stage_depth'] ?? null,
            'stage_notes' => $data['stage_notes'] ?? null,
            'lighting_notes' => $data['lighting_notes'] ?? null,
            'power_notes' => $data['power_notes'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $user->id,
        ]);

        foreach (self::LISTS as $list => $columns) {
            $rider->{$list} = $this->rows((array) ($data[$list] ?? []), $columns);
        }

        $rider->save();

        if ($stagePlot !== null) {
            $rider->addMedia($stagePlot)
                ->usingFileName('Buehnenplan_'.($this->pdf->fileSlug($band->name) ?: 'Band').'.'.$stagePlot->extension())
                ->toMediaCollection(MediaCollectionEnum::StagePlots->value);
        } elseif ($removeStagePlot) {
            $rider->clearMediaCollection(MediaCollectionEnum::StagePlots->value);
        }

        return $rider;
    }

    /**
     * @param  array<mixed>  $rows
     * @param  array{required: list<string>, text: list<string>, flags: list<string>}  $columns
     * @return list<array<string, string|bool>>
     */
    private function rows(array $rows, array $columns): array
    {
        $clean = [];

        foreach ($rows as $row) {
            $row = (array) $row;
            $entry = [];

            foreach ($columns['text'] as $column) {
                $entry[$column] = trim((string) ($row[$column] ?? ''));
            }

            foreach ($columns['flags'] as $column) {
                $entry[$column] = filter_var($row[$column] ?? false, FILTER_VALIDATE_BOOLEAN);
            }

            $isFilled = collect($columns['required'])->contains(fn (string $column): bool => $entry[$column] !== '');

            if ($isFilled) {
                $clean[] = $entry;
            }
        }

        return $clean;
    }
}
