<?php

namespace App\Actions\BandManager\Users;

use App\Models\BandManager\User;

/**
 * Recht laut config('band-manager.permissions') – unterstützt '*' und Wildcards wie 'gigs.*'.
 */
class CheckPermissionAction
{
    public function execute(User $user, string $permission): bool
    {
        foreach ((array) config('band-manager.permissions.'.$user->role->value, []) as $granted) {
            if ($granted === '*' || $granted === $permission) {
                return true;
            }

            if (str_ends_with($granted, '.*') && str_starts_with($permission, substr($granted, 0, -1))) {
                return true;
            }
        }

        return false;
    }
}
