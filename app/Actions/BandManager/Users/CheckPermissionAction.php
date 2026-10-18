<?php

namespace App\Actions\BandManager\Users;

use App\Enums\BandManager\AreaAccessEnum;
use App\Enums\BandManager\BandAreaEnum;
use App\Models\BandManager\User;

/**
 * Recht laut config('band-manager.permissions') – unterstützt '*' und Wildcards wie 'gigs.*'.
 * Zugänge einer Band bekommen zusätzlich die Rechte der Bereiche, die ihnen freigegeben wurden.
 */
class CheckPermissionAction
{
    public function execute(User $user, string $permission): bool
    {
        foreach ($this->grantedPermissions($user) as $granted) {
            if ($granted === '*' || $granted === $permission) {
                return true;
            }

            if (str_ends_with($granted, '.*') && str_starts_with($permission, substr($granted, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function grantedPermissions(User $user): array
    {
        $granted = array_values((array) config('band-manager.permissions.'.$user->role->value, []));

        if (! $user->role->isBandAccess()) {
            return $granted;
        }

        foreach ($user->area_access ?? [] as $area => $access) {
            $area = BandAreaEnum::tryFrom((string) $area);
            $access = AreaAccessEnum::tryFrom((string) $access);

            if ($area !== null && $access !== null) {
                array_push($granted, ...$area->permissions($access));
            }
        }

        return $granted;
    }
}
