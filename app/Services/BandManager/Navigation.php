<?php

namespace App\Services\BandManager;

use App\Models\BandManager\User;

/**
 * Menü der Seitenleiste (Icons: https://icons.getbootstrap.com).
 * Einträge mit section "admin" landen im abgetrennten Bereich "Administration".
 */
class Navigation
{
    public function __construct(private Exports $exports) {}

    /**
     * @return list<array{route: string, active: list<string>, label: string, icon: string, section: string, badge: int}>
     */
    public function for(User $user): array
    {
        $items = [
            ['route' => 'band-manager.admin.dashboard', 'label' => 'Dashboard', 'icon' => 'grid-1x2', 'order' => 10, 'visible' => $user->isAdmin()],
            ['route' => 'band-manager.home', 'label' => 'Start', 'icon' => 'house', 'order' => 10, 'visible' => ! $user->isAdmin() && ! $user->can('availability.view')],
            ['route' => 'band-manager.calendar.index', 'active' => ['band-manager.calendar.*'], 'label' => $user->isAdmin() ? 'Kalender' : 'Mein Kalender', 'icon' => 'calendar3', 'order' => 20, 'visible' => $user->can('availability.view')],
            ['route' => 'band-manager.team.index', 'active' => ['band-manager.team.*'], 'label' => 'Band/Team', 'icon' => 'person-vcard', 'order' => 25, 'visible' => $user->can('team.view')],
            ['route' => 'band-manager.gallery.index', 'active' => ['band-manager.gallery.*'], 'label' => 'Galerie', 'icon' => 'images', 'order' => 27, 'visible' => $user->can('gallery.view')],
            ['route' => 'band-manager.gigs.index', 'active' => ['band-manager.gigs.*'], 'label' => 'Gigs', 'icon' => 'music-note-beamed', 'order' => 30, 'visible' => $user->can('gigs.view')],
            ['route' => 'band-manager.documents.index', 'active' => ['band-manager.documents.*'], 'label' => 'Dokumente', 'icon' => 'file-earmark-text', 'order' => 40, 'visible' => $user->can('documents.view')],
            ['route' => 'band-manager.exports.index', 'active' => ['band-manager.exports.*', 'band-manager.admin.availability-export.*'], 'label' => 'Export', 'icon' => 'box-arrow-down', 'order' => 80, 'visible' => $this->exports->availableFor($user) !== []],

            ['route' => 'band-manager.admin.bands.index', 'active' => ['band-manager.admin.bands.*'], 'label' => 'Bands', 'icon' => 'people', 'order' => 40, 'section' => 'admin', 'visible' => $user->isAdmin()],
            ['route' => 'band-manager.admin.users.index', 'active' => ['band-manager.admin.users.*'], 'label' => 'Benutzer', 'icon' => 'person-gear', 'order' => 50, 'section' => 'admin', 'visible' => $user->isAdmin()],
        ];

        $items = array_values(array_filter($items, fn (array $item): bool => $item['visible']));
        usort($items, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

        return array_map(fn (array $item): array => [
            'route' => $item['route'],
            'active' => $item['active'] ?? [$item['route']],
            'label' => $item['label'],
            'icon' => $item['icon'],
            'section' => $item['section'] ?? 'main',
            'badge' => $item['badge'] ?? 0,
        ], $items);
    }
}
