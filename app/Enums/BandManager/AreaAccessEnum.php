<?php

namespace App\Enums\BandManager;

/**
 * Wie weit ein Zugang einer Band einen Bereich nutzen darf. Kein Eintrag = kein Zugriff.
 */
enum AreaAccessEnum: string
{
    case View = 'view';
    case Edit = 'edit';

    public function label(): string
    {
        return match ($this) {
            self::View => 'Ansehen',
            self::Edit => 'Bearbeiten',
        };
    }
}
