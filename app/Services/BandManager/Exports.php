<?php

namespace App\Services\BandManager;

use App\Models\BandManager\User;

/**
 * Alle Exporte – als Kacheln der Export-Seite, jeweils nur, wenn der Benutzer das nötige Recht hat.
 * Gibt es keinen, erscheint auch der Menüpunkt "Export" nicht.
 */
class Exports
{
    /**
     * @return list<array{route: string, active: list<string>, url: string, icon: string, title: string, description: string}>
     */
    public function availableFor(User $user): array
    {
        $exports = [
            [
                'ability' => 'presskit.create',
                'route' => 'band-manager.presskit.create',
                'active' => ['band-manager.presskit.*'],
                'icon' => 'newspaper',
                'title' => 'Pressekit zusammenstellen',
                'description' => 'Bandinfo (DE/EN), Fotos, Rider und Live-Termine – als ZIP mit PDFs.',
            ],
            [
                'ability' => 'riders.view',
                'route' => 'band-manager.tech-rider-export.index',
                'active' => ['band-manager.tech-rider-export.*'],
                'icon' => 'file-earmark-pdf',
                'title' => 'Technischer Rider',
                'description' => 'Tech Rider mit Kanalliste, Monitoring, Backline und Bühnenplan – als PDF.',
            ],
            [
                'ability' => 'riders.view',
                'route' => 'band-manager.catering-rider-export.index',
                'active' => ['band-manager.catering-rider-export.*'],
                'icon' => 'cup-hot',
                'title' => 'Catering Rider',
                'description' => 'Reisegruppe mit Ernährung, Mahlzeiten, Buyout, Getränke und Garderobe – als PDF.',
            ],
            [
                'ability' => 'export.csv',
                'route' => 'band-manager.admin.availability-export.index',
                'active' => ['band-manager.admin.availability-export.*'],
                'icon' => 'filetype-csv',
                'title' => 'Verfügbarkeiten (CSV)',
                'description' => 'Ampel-Kalender eines Monats als Excel-Tabelle.',
            ],
        ];

        $available = [];

        foreach ($exports as $export) {
            if ($user->can($export['ability'])) {
                $available[] = [
                    'route' => $export['route'],
                    'active' => $export['active'],
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
