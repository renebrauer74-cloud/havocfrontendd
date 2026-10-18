<?php

namespace App\Actions\BandManager\Users;

use App\Models\BandManager\User;

/**
 * Startseite nach dem Login: Agentur → Dashboard, Bands → Kalender. Zugänge ohne Kalender landen im ersten
 * freigegebenen Bereich, ganz ohne Bereich auf der eigenen Startseite.
 */
class ResolveHomeUrlAction
{
    /**
     * Recht => Route, in der Reihenfolge der Seitenleiste.
     */
    private const AREA_ROUTES = [
        'availability.view' => 'band-manager.calendar.index',
        'team.view' => 'band-manager.team.index',
        'gallery.view' => 'band-manager.gallery.index',
        'gigs.view' => 'band-manager.gigs.index',
        'messages.view' => 'band-manager.messages.index',
        'documents.view' => 'band-manager.documents.index',
        'presskit.create' => 'band-manager.presskit.create',
    ];

    public function __construct(private CheckPermissionAction $checkPermission) {}

    public function execute(User $user): string
    {
        if ($user->isAdmin()) {
            return route('band-manager.admin.dashboard');
        }

        foreach (self::AREA_ROUTES as $permission => $route) {
            if ($this->checkPermission->execute($user, $permission)) {
                return route($route);
            }
        }

        return route('band-manager.home');
    }
}
