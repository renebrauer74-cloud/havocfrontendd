<?php

namespace App\Actions\BandManager\Riders;

/**
 * Ernährung und Allergien auf einen Blick für die Küche: gleiche Angaben zusammengefasst (häufigste zuerst),
 * mehrere Allergien einer Person („Nüsse, Sellerie“) einzeln gezählt – und wie viele Personen ohne Einschränkung essen.
 */
class SummarizeCateringDietsAction
{
    /**
     * @param  array<int, array<string, mixed>>  $people  Reisegruppe aus dem Catering Rider
     * @return array{diets: list<array{label: string, count: int, names: list<string>}>, allergies: list<array{label: string, count: int, names: list<string>}>, without: int}
     */
    public function execute(array $people): array
    {
        $diets = [];
        $allergies = [];
        $without = 0;

        foreach ($people as $person) {
            $name = trim((string) ($person['name'] ?? ''));
            $diet = trim((string) ($person['diet'] ?? ''));
            $allergyList = array_filter(array_map('trim', preg_split('/\s*(?:[,;+]|\bund\b)\s*/u', (string) ($person['allergy'] ?? '')) ?: []));

            if ($diet === '' && $allergyList === []) {
                $without++;

                continue;
            }

            if ($diet !== '') {
                $this->count($diets, $diet, $name);
            }

            foreach ($allergyList as $allergy) {
                $this->count($allergies, $allergy, $name);
            }
        }

        return ['diets' => $this->sorted($diets), 'allergies' => $this->sorted($allergies), 'without' => $without];
    }

    /**
     * @param  array<string, array{label: string, count: int, names: list<string>}>  $groups
     */
    private function count(array &$groups, string $label, string $name): void
    {
        $key = mb_strtolower($label);
        $groups[$key] ??= ['label' => $label, 'count' => 0, 'names' => []];
        $groups[$key]['count']++;
        $groups[$key]['names'][] = $name;
    }

    /**
     * @param  array<string, array{label: string, count: int, names: list<string>}>  $groups
     * @return list<array{label: string, count: int, names: list<string>}>
     */
    private function sorted(array $groups): array
    {
        $groups = array_values($groups);
        usort($groups, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return $groups;
    }
}
