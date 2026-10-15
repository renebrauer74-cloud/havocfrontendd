<?php

namespace App\Policies\BandManager;

use App\Models\BandManager\Document;
use App\Models\BandManager\User;

/**
 * Agentur (documents.manage) darf alles – über Gate::before. Bands sehen die Dokumente ihrer Bands
 * und dürfen nur Rider löschen.
 */
class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->can('documents.view') && $user->can('access', $document->band);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('documents.manage')
            || (! $document->isContract() && $user->can('documents.upload') && $user->can('access', $document->band));
    }
}
