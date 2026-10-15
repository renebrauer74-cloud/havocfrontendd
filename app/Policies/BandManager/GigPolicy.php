<?php

namespace App\Policies\BandManager;

use App\Models\BandManager\Gig;
use App\Models\BandManager\User;

/**
 * Agentur (gigs.manage) darf alles – über Gate::before. Bands nur ihre selbst angelegten Shows.
 */
class GigPolicy
{
    /**
     * Day Sheet (PDF) – für alle mit Zugriff auf die Band.
     */
    public function view(User $user, Gig $gig): bool
    {
        return $user->can('gigs.view') && $user->can('access', $gig->band);
    }

    public function update(User $user, Gig $gig): bool
    {
        return $user->can('gigs.create') && $gig->isBandOwned() && $user->can('access', $gig->band);
    }

    public function delete(User $user, Gig $gig): bool
    {
        return $this->update($user, $gig);
    }

    /**
     * Gage und interne Notizen: Agentur immer, Bands nur bei eigenen Shows.
     */
    public function viewDetails(User $user, Gig $gig): bool
    {
        return $user->can('gigs.manage') || $gig->isBandOwned();
    }
}
