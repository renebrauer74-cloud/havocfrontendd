<?php

namespace App\Services\BandManager;

use App\Actions\BandManager\Messages\CountUnreadThreadsAction;
use App\Actions\BandManager\Users\ResolveHomeUrlAction;
use App\Models\BandManager\User;

/**
 * Menü der Seitenleiste (Icons: https://icons.getbootstrap.com).
 * Einträge mit section "riders" landen unter der Überschrift "Rider Creator", section "website" unter "Website Builder",
 * darunter steht die farbig hervorgehobene "Cloud" (section "cloud") für sich,
 * der hervorgehobene Punkt "Export" (section "exports") führt zur Kachel-Seite mit allen Exporten,
 * Einträge mit section "admin" im abgetrennten Bereich "Administration".
 */
class Navigation
{
    public function __construct(private Exports $exports) {}

    /**
     * @return list<array{route: string, active: list<string>, label: string, icon: string, section: string, badge: int}>
     */
    public function for(User $user): array
    {
        // Ein Menüpunkt für die Export-Seite – aktiv auch auf den einzelnen Exporten
        $exports = $this->exports->availableFor($user);
        $exportActive = ['band-manager.exports.*', ...array_merge(...array_column($exports, 'active'))];

        $items = [
            ['route' => 'band-manager.admin.dashboard', 'label' => 'Dashboard', 'icon' => 'grid-1x2', 'order' => 10, 'visible' => $user->isAdmin()],
            ['route' => 'band-manager.home', 'label' => 'Start', 'icon' => 'house', 'order' => 10, 'visible' => app(ResolveHomeUrlAction::class)->execute($user) === route('band-manager.home')],
            ['route' => 'band-manager.calendar.index', 'active' => ['band-manager.calendar.*'], 'label' => $user->isAdmin() ? 'Kalender' : 'Mein Kalender', 'icon' => 'calendar3', 'order' => 20, 'visible' => $user->can('availability.view')],
            ['route' => 'band-manager.team.index', 'active' => ['band-manager.team.*'], 'label' => 'Band/Team', 'icon' => 'person-vcard', 'order' => 25, 'visible' => $user->can('team.view')],
            ['route' => 'band-manager.gigs.index', 'active' => ['band-manager.gigs.*'], 'label' => 'Gigs', 'icon' => 'music-note-beamed', 'order' => 30, 'visible' => $user->can('gigs.view')],
            ['route' => 'band-manager.gallery.index', 'active' => ['band-manager.gallery.*'], 'label' => 'Galerie', 'icon' => 'images', 'order' => 32, 'visible' => $user->can('gallery.view')],
            ['route' => 'band-manager.messages.index', 'active' => ['band-manager.messages.*'], 'label' => 'Nachrichten', 'icon' => 'chat-dots', 'order' => 35, 'visible' => $user->can('messages.view'),
                'badge' => $user->can('messages.view') ? app(CountUnreadThreadsAction::class)->execute($user) : 0],
            ['route' => 'band-manager.documents.index', 'active' => ['band-manager.documents.*'], 'label' => 'Dokumente', 'icon' => 'file-earmark-text', 'order' => 40, 'visible' => $user->can('documents.view')],
            // Die Agentur verwaltet alle Logins unter Administration → Benutzer
            ['route' => 'band-manager.accesses.index', 'active' => ['band-manager.accesses.*'], 'label' => 'Zugänge', 'icon' => 'key', 'order' => 90, 'visible' => ! $user->isAdmin() && $user->can('logins.manage')],

            ['route' => 'band-manager.tech-riders.index', 'active' => ['band-manager.tech-riders.*'], 'label' => 'Tech Rider', 'icon' => 'sliders2-vertical', 'order' => 10, 'section' => 'riders', 'visible' => $user->can('riders.view')],
            ['route' => 'band-manager.catering-riders.index', 'active' => ['band-manager.catering-riders.*'], 'label' => 'Catering Rider', 'icon' => 'cup-hot', 'order' => 20, 'section' => 'riders', 'visible' => $user->can('riders.view')],
            ['route' => 'band-manager.stage-plots.index', 'active' => ['band-manager.stage-plots.*'], 'label' => 'Stage Plot Designer', 'icon' => 'easel2', 'order' => 30, 'section' => 'riders', 'visible' => $user->can('riders.view')],

            ['route' => 'band-manager.website-creator.index', 'active' => ['band-manager.website-creator.*'], 'label' => 'Website Creator', 'icon' => 'globe2', 'order' => 10, 'section' => 'website', 'visible' => $user->can('website.view')],

            ['route' => 'band-manager.cloud.index', 'active' => ['band-manager.cloud.*'], 'label' => 'Cloud', 'icon' => 'cloud', 'order' => 10, 'section' => 'cloud', 'visible' => $user->can('cloud.view')],

            ['route' => 'band-manager.exports.index', 'active' => $exportActive, 'label' => 'Export', 'icon' => 'box-arrow-down', 'order' => 10, 'section' => 'exports', 'visible' => $exports !== []],

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
