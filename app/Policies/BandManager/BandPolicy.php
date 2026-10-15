<?php

namespace App\Policies\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\User;

class BandPolicy
{
    /**
     * Zugriff auf die Daten einer Band (Kalender, Gigs, Team …): Bands nur auf die eigenen.
     * Admins dürfen über Gate::before ohnehin alles.
     */
    public function access(User $user, Band $band): bool
    {
        return $user->bands()->whereKey($band->getKey())->exists();
    }
}
