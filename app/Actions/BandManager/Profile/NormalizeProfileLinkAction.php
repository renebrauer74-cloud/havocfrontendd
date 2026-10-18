<?php

namespace App\Actions\BandManager\Profile;

use App\Enums\BandManager\PlatformEnum;

/**
 * Prüft einen Profil-Link – „@name“ wird bei Instagram, TikTok … zur vollständigen Adresse. null = ungültig.
 */
class NormalizeProfileLinkAction
{
    public function execute(string $value, PlatformEnum $platform): ?string
    {
        $url = trim($value);

        if (preg_match('/^@([A-Za-z0-9._-]{1,100})$/', $url, $matches)) {
            if ($platform->handleUrl() === null) {
                return null;
            }

            $url = sprintf($platform->handleUrl(), $matches[1]);
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return str_contains((string) parse_url($url, PHP_URL_HOST), '.') ? mb_substr($url, 0, 255) : null;
    }
}
