<?php

namespace App\Enums\BandManager;

/**
 * Bereiche des Band-Managers, die eine Band ihren Zugängen (Bandmitglied, Team, Fotograf) einzeln freigeben kann.
 */
enum BandAreaEnum: string
{
    case Calendar = 'calendar';
    case Gigs = 'gigs';
    case Team = 'team';
    case Gallery = 'gallery';
    case Documents = 'documents';
    case Messages = 'messages';
    case Presskit = 'presskit';
    case Riders = 'riders';

    public function label(): string
    {
        return match ($this) {
            self::Calendar => 'Kalender',
            self::Gigs => 'Gigs & Day Sheets',
            self::Team => 'Band/Team & Bandprofil',
            self::Gallery => 'Galerie',
            self::Documents => 'Verträge & Rider',
            self::Messages => 'Nachrichten',
            self::Presskit => 'Pressekit',
            self::Riders => 'Rider Creator (Tech Rider)',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Calendar => 'calendar3',
            self::Gigs => 'music-note-beamed',
            self::Team => 'person-vcard',
            self::Gallery => 'images',
            self::Documents => 'file-earmark-text',
            self::Messages => 'chat-dots',
            self::Presskit => 'box-arrow-down',
            self::Riders => 'sliders2-vertical',
        };
    }

    /**
     * Nachrichten und Pressekit kennen kein „nur ansehen“ – dort gibt es nur Zugriff oder keinen.
     */
    public function hasEditLevel(): bool
    {
        return ! in_array($this, [self::Messages, self::Presskit], true);
    }

    /**
     * Rechte (siehe config('band-manager.permissions')), die der Bereich mit dieser Stufe gewährt.
     *
     * @return list<string>
     */
    public function permissions(AreaAccessEnum $access): array
    {
        $edit = $access === AreaAccessEnum::Edit;

        return match ($this) {
            self::Calendar => $edit ? ['availability.view', 'availability.edit'] : ['availability.view'],
            self::Gigs => $edit ? ['gigs.view', 'gigs.create'] : ['gigs.view'],
            self::Team => $edit ? ['team.view', 'team.edit'] : ['team.view'],
            self::Gallery => $edit ? ['gallery.view', 'gallery.edit'] : ['gallery.view'],
            self::Documents => $edit ? ['documents.view', 'documents.upload'] : ['documents.view'],
            self::Messages => ['messages.view'],
            self::Presskit => ['presskit.create'],
            self::Riders => $edit ? ['riders.view', 'riders.edit'] : ['riders.view'],
        };
    }
}
