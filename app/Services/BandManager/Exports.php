<?php

namespace App\Services\BandManager;

use App\Models\BandManager\User;

/**
 * Kacheln der Export-Seite – jeweils nur, wenn der Benutzer das nötige Recht hat.
 * Gibt es keine Kachel, erscheint auch der Menüpunkt "Export" nicht.
 */
class Exports
{
    /**
     * @return list<array{url: string, icon: string, title: string, description: string}>
     */
    public function availableFor(User $user): array
    {
        $exports = [
            [
                'ability' => 'export.csv',
                'route' => 'band-manager.admin.availability-export.index',
                'icon' => 'filetype-csv',
                'title' => 'Verfügbarkeiten (CSV)',
                'description' => 'Ampel-Kalender eines Monats als Excel-Tabelle.',
            ],
        ];

        $available = [];

        foreach ($exports as $export) {
            if ($user->can($export['ability'])) {
                $available[] = [
                    'url' => route($export['route']),
                    'icon' => $export['icon'],
                    'title' => $export['title'],
                    'description' => $export['description'],
                ];
            }
        }

        return $available;
    }
}
