<?php

namespace App\Enums\BandManager;

/**
 * Abschnitte der Band-Website unter dem Hero. Die Reihenfolge der Cases ist die Standard-Reihenfolge,
 * die Band kann sie im Website Creator per Drag & Drop ändern und Abschnitte ausblenden.
 * Überschriften auf der Website: lang/{de,en}/band-website.php → sections.
 */
enum WebsiteSectionEnum: string
{
    case About = 'about';
    case Music = 'music';
    case Shows = 'shows';
    case Gallery = 'gallery';
    case Video = 'video';
    case Members = 'members';
    case Links = 'links';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::About => 'Über uns',
            self::Music => 'Musik (Spotify)',
            self::Shows => 'Live-Termine',
            self::Gallery => 'Galerie',
            self::Video => 'Video (YouTube)',
            self::Members => 'Bandmitglieder',
            self::Links => 'Social Media & Streaming',
            self::Contact => 'Kontakt & Booking',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::About => 'person-lines-fill',
            self::Music => 'spotify',
            self::Shows => 'ticket-perforated',
            self::Gallery => 'images',
            self::Video => 'youtube',
            self::Members => 'people',
            self::Links => 'share',
            self::Contact => 'envelope-paper',
        };
    }

    /**
     * Woher der Abschnitt seine Inhalte holt (Hinweis im Website Creator).
     */
    public function source(): string
    {
        return match ($this) {
            self::About => 'Biografie aus dem Bandprofil oder eigener Text',
            self::Music => 'Spotify-Link aus dem Bandprofil oder eigener Link',
            self::Shows => 'Bestätigte, kommende Gigs (Datum, Stadt, Location)',
            self::Gallery => 'Ausgewählte Fotos aus der Galerie',
            self::Video => 'YouTube-Video, das ihr unten eintragt',
            self::Members => 'Bandmitglieder aus Band/Team mit Foto und Instrument',
            self::Links => 'Alle Links aus dem Bandprofil',
            self::Contact => 'Kontakt-E-Mail der Band und Booking der Agentur',
        };
    }

    /**
     * Standard: alle Abschnitte in Enum-Reihenfolge, alle eingeblendet.
     *
     * @return list<array{key: string, enabled: bool}>
     */
    public static function defaults(): array
    {
        return array_map(fn (self $section): array => ['key' => $section->value, 'enabled' => true], self::cases());
    }
}
