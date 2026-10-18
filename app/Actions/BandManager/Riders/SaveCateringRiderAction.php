<?php

namespace App\Actions\BandManager\Riders;

use App\Enums\BandManager\CateringBuyoutEnum;
use App\Enums\BandManager\CateringMealEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\CateringRider;
use App\Models\BandManager\User;

/**
 * Speichert den Catering Rider einer Band. Leere Zeilen der Listen fallen weg, die Reihenfolge bleibt erhalten.
 * Mahlzeiten werden immer vollständig (je CateringMealEnum) gespeichert, der Buyout-Betrag nur bei Buyout.
 */
class SaveCateringRiderAction
{
    /**
     * Spalten je Liste – eine Spalte aus "required" muss ausgefüllt sein, damit eine Zeile gespeichert wird.
     *
     * @var array<string, array{required: list<string>, text: list<string>}>
     */
    private const array LISTS = [
        'people' => ['required' => ['name', 'diet', 'allergy'], 'text' => ['name', 'role', 'diet', 'allergy']],
        'drinks' => ['required' => ['item'], 'text' => ['item', 'amount', 'note']],
        'custom_sections' => ['required' => ['title', 'content'], 'text' => ['title', 'content']],
    ];

    /**
     * @param  array<string, mixed>  $data  validierte Eingaben aus CateringRiderRequest
     */
    public function execute(Band $band, array $data, User $user): CateringRider
    {
        $rider = $band->cateringRider;
        $buyout = CateringBuyoutEnum::tryFrom((string) ($data['buyout'] ?? '')) ?? CateringBuyoutEnum::Catering;

        $rider->fill([
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'diet_notes' => $data['diet_notes'] ?? null,
            'meals' => $this->meals((array) ($data['meals'] ?? [])),
            'buyout' => $buyout,
            'buyout_amount' => $buyout->hasBuyout() ? ($data['buyout_amount'] ?? null) : null,
            'buyout_notes' => $data['buyout_notes'] ?? null,
            'drink_notes' => $data['drink_notes'] ?? null,
            'dressing_rooms' => $data['dressing_rooms'] ?? null,
            'stage_towels' => $data['stage_towels'] ?? null,
            'shower_towels' => $data['shower_towels'] ?? null,
            'needs_shower' => (bool) ($data['needs_shower'] ?? false),
            'dressing_room_notes' => $data['dressing_room_notes'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $user->id,
        ]);

        foreach (self::LISTS as $list => $columns) {
            $rider->{$list} = $this->rows((array) ($data[$list] ?? []), $columns);
        }

        $rider->save();

        return $rider;
    }

    /**
     * @param  array<mixed>  $meals
     * @return array<string, array{wanted: bool, time: string, wishes: string}>
     */
    private function meals(array $meals): array
    {
        $clean = [];

        foreach (CateringMealEnum::cases() as $meal) {
            $values = (array) ($meals[$meal->value] ?? []);

            $clean[$meal->value] = [
                'wanted' => filter_var($values['wanted'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'time' => trim((string) ($values['time'] ?? '')),
                'wishes' => trim((string) ($values['wishes'] ?? '')),
            ];
        }

        return $clean;
    }

    /**
     * @param  array<mixed>  $rows
     * @param  array{required: list<string>, text: list<string>}  $columns
     * @return list<array<string, string>>
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

            $isFilled = collect($columns['required'])->contains(fn (string $column): bool => $entry[$column] !== '');

            if ($isFilled) {
                $clean[] = $entry;
            }
        }

        return $clean;
    }
}
