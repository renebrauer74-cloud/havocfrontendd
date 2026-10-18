<?php

namespace App\Actions\BandManager\Riders;

use App\Models\BandManager\Band;

/**
 * Ergänzt die gespeicherte Reisegruppe um Ernährung und Allergie aus „Band/Team“ – nur leere Felder,
 * Personen werden über den Namen zugeordnet. Was im Catering Rider eingetragen ist, bleibt stehen.
 */
class FillCateringRiderPeopleFromTeamAction
{
    private const array FIELDS = ['diet', 'allergy'];

    public function __construct(private SuggestCateringRiderPeopleAction $suggestPeople) {}

    /**
     * @param  array<int, array<string, mixed>>  $people  gespeicherte Reisegruppe
     * @return list<array<string, mixed>>
     */
    public function execute(Band $band, array $people): array
    {
        $team = collect($this->suggestPeople->execute($band))->keyBy(fn (array $person): string => mb_strtolower($person['name']));

        return array_map(function (array $person) use ($team): array {
            $match = $team->get(mb_strtolower(trim((string) ($person['name'] ?? ''))));

            foreach (self::FIELDS as $field) {
                if ($match !== null && trim((string) ($person[$field] ?? '')) === '') {
                    $person[$field] = $match[$field];
                }
            }

            return $person;
        }, array_values($people));
    }
}
