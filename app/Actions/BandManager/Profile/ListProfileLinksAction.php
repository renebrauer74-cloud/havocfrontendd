<?php

namespace App\Actions\BandManager\Profile;

use App\Enums\BandManager\PlatformEnum;
use App\Models\BandManager\BandProfile;

/**
 * Ausgefüllte Links eines Bandprofils in der Reihenfolge der Plattformen.
 * „display“ ist die Kurzform für die Anzeige: "https://www.instagram.com/band/" → "instagram.com/band".
 */
class ListProfileLinksAction
{
    /**
     * @return list<array{platform: PlatformEnum, url: string, display: string}>
     */
    public function execute(BandProfile $profile): array
    {
        $links = [];

        foreach (PlatformEnum::cases() as $platform) {
            $url = $profile->links[$platform->value] ?? '';

            if (is_string($url) && $url !== '') {
                $links[] = [
                    'platform' => $platform,
                    'url' => $url,
                    'display' => rtrim((string) preg_replace('#^https?://(www\.)?#i', '', $url), '/'),
                ];
            }
        }

        return $links;
    }
}
