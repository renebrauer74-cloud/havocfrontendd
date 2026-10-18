<?php

namespace App\Enums\BandManager;

/**
 * Social Media & Streaming im Bandprofil. Die Reihenfolge der Cases ist die Anzeige-Reihenfolge,
 * der Wert der Schlüssel in bm_band_profiles.links.
 */
enum PlatformEnum: string
{
    case Website = 'website';
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';
    case Spotify = 'spotify';
    case AppleMusic = 'apple_music';
    case AmazonMusic = 'amazon_music';
    case Deezer = 'deezer';
    case SoundCloud = 'soundcloud';
    case Bandcamp = 'bandcamp';
    case X = 'x';
    case Threads = 'threads';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook',
            self::TikTok => 'TikTok',
            self::YouTube => 'YouTube',
            self::Spotify => 'Spotify',
            self::AppleMusic => 'Apple Music',
            self::AmazonMusic => 'Amazon Music',
            self::Deezer => 'Deezer',
            self::SoundCloud => 'SoundCloud',
            self::Bandcamp => 'Bandcamp',
            self::X => 'X (Twitter)',
            self::Threads => 'Threads',
        };
    }

    /**
     * Name des Bootstrap-Icons (bi-…).
     */
    public function icon(): string
    {
        return match ($this) {
            self::Website => 'globe2',
            self::Instagram => 'instagram',
            self::Facebook => 'facebook',
            self::TikTok => 'tiktok',
            self::YouTube => 'youtube',
            self::Spotify => 'spotify',
            self::AppleMusic => 'apple',
            self::AmazonMusic => 'amazon',
            self::Deezer => 'soundwave',
            self::SoundCloud => 'cloud-fill',
            self::Bandcamp => 'vinyl-fill',
            self::X => 'twitter-x',
            self::Threads => 'threads',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Website => '#6b7280',
            self::Instagram => '#E4405F',
            self::Facebook => '#1877F2',
            self::TikTok, self::X, self::Threads => '#111111',
            self::YouTube => '#FF0000',
            self::Spotify => '#1DB954',
            self::AppleMusic => '#FA243C',
            self::AmazonMusic => '#00A8E1',
            self::Deezer => '#A238FF',
            self::SoundCloud => '#FF5500',
            self::Bandcamp => '#1DA0C3',
        };
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::Website => 'https://www.bandname.de',
            self::Instagram, self::TikTok, self::YouTube, self::X, self::Threads => '@bandname',
            self::Facebook => 'https://facebook.com/bandname',
            self::Spotify => 'https://open.spotify.com/artist/…',
            self::AppleMusic => 'https://music.apple.com/…',
            self::AmazonMusic => 'https://music.amazon.de/artists/…',
            self::Deezer => 'https://www.deezer.com/artist/…',
            self::SoundCloud => 'https://soundcloud.com/bandname',
            self::Bandcamp => 'https://bandname.bandcamp.com',
        };
    }

    /**
     * Vorlage, um aus „@name“ die vollständige Adresse zu machen – null = „@name“ nicht erlaubt.
     */
    public function handleUrl(): ?string
    {
        return match ($this) {
            self::Instagram => 'https://www.instagram.com/%s/',
            self::Facebook => 'https://www.facebook.com/%s',
            self::TikTok => 'https://www.tiktok.com/@%s',
            self::YouTube => 'https://www.youtube.com/@%s',
            self::X => 'https://x.com/%s',
            self::Threads => 'https://www.threads.net/@%s',
            default => null,
        };
    }
}
