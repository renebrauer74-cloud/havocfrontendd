<?php

namespace App\Enums\BandManager;

enum RiderCategoryEnum: string
{
    case Technical = 'technical';
    case Catering = 'catering';
    case Stageplot = 'stageplot';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Technical Rider',
            self::Catering => 'Catering Rider',
            self::Stageplot => 'Stageplot / Inputliste',
            self::Other => 'Sonstiges',
        };
    }
}
