<?php

namespace App\Enums\BandManager;

/**
 * Was Externe über einen Freigabe-Link im Cloud-Speicher dürfen.
 */
enum CloudLinkTypeEnum: string
{
    case Read = 'read';
    case Upload = 'upload';

    public function label(): string
    {
        return match ($this) {
            self::Read => 'Ansehen & herunterladen',
            self::Upload => 'Hochladen, ansehen & herunterladen',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Read => 'eye',
            self::Upload => 'cloud-arrow-up',
        };
    }

    public function allowsUpload(): bool
    {
        return $this === self::Upload;
    }
}
