<?php

namespace App\Enums\BandManager;

/**
 * Was Externe über einen Freigabe-Link im Cloud-Speicher dürfen.
 */
enum CloudLinkTypeEnum: string
{
    case Read = 'read';

    public function label(): string
    {
        return match ($this) {
            self::Read => 'Ansehen & herunterladen',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Read => 'eye',
        };
    }
}
