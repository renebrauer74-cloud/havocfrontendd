<?php

namespace App\Actions\BandManager\Users;

use App\Models\BandManager\User;

/**
 * Startseite nach dem Login: Agentur → Dashboard, Bands → Kalender (sonst eigene Startseite).
 */
class ResolveHomeUrlAction
{
    public function __construct(private CheckPermissionAction $checkPermission) {}

    public function execute(User $user): string
    {
        if ($user->isAdmin()) {
            return route('band-manager.admin.dashboard');
        }

        return $this->checkPermission->execute($user, 'availability.view')
            ? route('band-manager.calendar.index')
            : route('band-manager.home');
    }
}
