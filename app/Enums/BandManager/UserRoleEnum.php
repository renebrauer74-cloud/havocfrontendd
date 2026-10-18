<?php

namespace App\Enums\BandManager;

/**
 * Rolle eines Logins im Band-Manager. Die Rechte je Rolle stehen in config('band-manager.permissions').
 * Zugänge einer Band (Bandmitglied, Team, Fotograf) bekommen ihre Rechte zusätzlich über die freigegebenen Bereiche.
 */
enum UserRoleEnum: string
{
    case Admin = 'admin';
    case Artist = 'artist';
    case BandMember = 'band_member';
    case Team = 'team';
    case Photographer = 'photographer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator / Agentur',
            self::Artist => 'Künstler / Band',
            self::BandMember => 'Bandmitglied',
            self::Team => 'Team',
            self::Photographer => 'Fotograf',
        };
    }

    /**
     * Zugang, den eine Band selbst anlegt und dem sie einzelne Bereiche freigibt.
     */
    public function isBandAccess(): bool
    {
        return in_array($this, self::bandAccessCases(), true);
    }

    /**
     * Vorbelegung der Bereiche beim Anlegen eines Zugangs – die Band kann sie danach anpassen.
     *
     * @return array<string, AreaAccessEnum>
     */
    public function defaultAreaAccess(): array
    {
        return match ($this) {
            self::BandMember => [
                BandAreaEnum::Calendar->value => AreaAccessEnum::Edit,
                BandAreaEnum::Gigs->value => AreaAccessEnum::View,
                BandAreaEnum::Team->value => AreaAccessEnum::Edit,
                BandAreaEnum::Gallery->value => AreaAccessEnum::Edit,
                BandAreaEnum::Documents->value => AreaAccessEnum::View,
                BandAreaEnum::Messages->value => AreaAccessEnum::View,
                BandAreaEnum::Presskit->value => AreaAccessEnum::View,
                BandAreaEnum::Riders->value => AreaAccessEnum::Edit,
            ],
            self::Team => [
                BandAreaEnum::Calendar->value => AreaAccessEnum::View,
                BandAreaEnum::Gigs->value => AreaAccessEnum::View,
                BandAreaEnum::Team->value => AreaAccessEnum::View,
                BandAreaEnum::Documents->value => AreaAccessEnum::View,
                BandAreaEnum::Riders->value => AreaAccessEnum::View,
            ],
            self::Photographer => [
                BandAreaEnum::Team->value => AreaAccessEnum::View,
                BandAreaEnum::Gallery->value => AreaAccessEnum::Edit,
            ],
            default => [],
        };
    }

    /**
     * @return list<self>
     */
    public static function bandAccessCases(): array
    {
        return [self::BandMember, self::Team, self::Photographer];
    }
}
