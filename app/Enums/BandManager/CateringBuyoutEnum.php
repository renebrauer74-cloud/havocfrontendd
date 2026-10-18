<?php

namespace App\Enums\BandManager;

/**
 * Catering vor Ort oder Buyout (Geld statt Essen) – Catering Rider.
 */
enum CateringBuyoutEnum: string
{
    case Catering = 'catering';
    case Optional = 'optional';
    case Preferred = 'preferred';

    public function label(): string
    {
        return match ($this) {
            self::Catering => 'Catering vor Ort',
            self::Optional => 'Catering oder Buyout',
            self::Preferred => 'Buyout gewünscht',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Catering => 'Wir möchten vor Ort verpflegt werden – kein Buyout.',
            self::Optional => 'Am liebsten Catering, ein Buyout ist nach Absprache in Ordnung.',
            self::Preferred => 'Statt Essen lieber einen Betrag pro Person (Getränke und Garderobe bleiben).',
        };
    }

    public function hasBuyout(): bool
    {
        return $this !== self::Catering;
    }
}
