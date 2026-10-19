<?php

namespace App\Actions\BandManager\Website;

use App\Enums\BandManager\WebsiteSectionEnum;

/**
 * Abschnitte einer Website in der gespeicherten Reihenfolge. Unbekannte und doppelte Einträge fallen weg,
 * fehlende (z. B. später neu eingeführte) Abschnitte kommen eingeblendet ans Ende.
 */
class ListWebsiteSectionsAction
{
    /**
     * @param  array<mixed>|null  $sections  [['key' => 'about', 'enabled' => true], …]
     * @return list<array{section: WebsiteSectionEnum, enabled: bool}>
     */
    public function execute(?array $sections): array
    {
        $list = [];

        foreach ($sections ?? [] as $entry) {
            $section = WebsiteSectionEnum::tryFrom((string) (((array) $entry)['key'] ?? ''));

            if ($section !== null && ! isset($list[$section->value])) {
                $list[$section->value] = ['section' => $section, 'enabled' => (bool) (((array) $entry)['enabled'] ?? false)];
            }
        }

        foreach (WebsiteSectionEnum::cases() as $section) {
            $list[$section->value] ??= ['section' => $section, 'enabled' => true];
        }

        return array_values($list);
    }
}
