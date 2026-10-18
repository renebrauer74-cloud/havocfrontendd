<?php

namespace App\Enums\BandManager;

/**
 * Art eines Monitorwegs (Tech Rider).
 */
enum MonitorTypeEnum: string
{
    case Wedge = 'wedge';
    case InEarWired = 'iem_wired';
    case InEarWireless = 'iem_wireless';
    case Drumfill = 'drumfill';
    case Sidefill = 'sidefill';

    public function label(): string
    {
        return match ($this) {
            self::Wedge => 'Wedge (Bodenmonitor)',
            self::InEarWired => 'In-Ear (Kabel)',
            self::InEarWireless => 'In-Ear (Funk)',
            self::Drumfill => 'Drumfill',
            self::Sidefill => 'Sidefill',
        };
    }
}
