<?php

namespace App\Enums\BandManager;

/**
 * Rolle eines Logins im Band-Manager. Die Rechte je Rolle stehen in config('band-manager.permissions').
 */
enum UserRoleEnum: string
{
    case Admin = 'admin';
    case Artist = 'artist';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator / Agentur',
            self::Artist => 'Künstler / Band',
        };
    }
}
