<?php

namespace App\Enums\BandManager;

enum GigStatusEnum: string
{
    case Request = 'request';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Request => 'Anfrage',
            self::Confirmed => 'Bestätigt',
            self::Cancelled => 'Abgesagt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Request => '#8b5cf6',
            self::Confirmed => '#2563eb',
            self::Cancelled => '#6b7280',
        };
    }
}
