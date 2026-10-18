<?php

namespace App\Enums\BandManager;

/**
 * Aufgabe eines Crew-Mitglieds. Bei „Sonstiges“ steht die Aufgabe in crew_role_other.
 */
enum CrewRoleEnum: string
{
    case Merch = 'merch';
    case Sound = 'sound';
    case Monitor = 'monitor';
    case Light = 'light';
    case TourManager = 'tour_manager';
    case Driver = 'driver';
    case Backline = 'backline';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Merch => 'Merchandise',
            self::Sound => 'Tontechnik',
            self::Monitor => 'Monitortechnik',
            self::Light => 'Lichttechnik',
            self::TourManager => 'Tourmanagement',
            self::Driver => 'Fahrer',
            self::Backline => 'Backline / Roadie',
            self::Other => 'Sonstiges',
        };
    }
}
