<?php

namespace App\Enums\BandManager;

/**
 * Symbole des Stage Plot Designers – das Bild liegt als <symbol id="sp-{wert}"> in
 * resources/views/band-manager/riders/stage-plot/symbols.blade.php (Draufsicht, Publikum unten).
 */
enum StagePlotElementEnum: string
{
    case Person = 'person';
    case Microphone = 'microphone';
    case InEar = 'in_ear';
    case Drums = 'drums';
    case GuitarAmp = 'guitar_amp';
    case BassAmp = 'bass_amp';
    case Keyboard = 'keyboard';
    case AcousticGuitar = 'acoustic_guitar';
    case GuitarStand = 'guitar_stand';
    case Mandolin = 'mandolin';
    case Banjo = 'banjo';
    case Violin = 'violin';
    case Flute = 'flute';
    case Accordion = 'accordion';
    case Playback = 'playback';
    case Wedge = 'wedge';
    case Drumfill = 'drumfill';
    case Sidefill = 'sidefill';
    case DiBox = 'di_box';
    case Power = 'power';
    case Riser = 'riser';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Person => 'Musiker / Person',
            self::Microphone => 'Mikrofon (Galgen)',
            self::InEar => 'In-Ear (IEM)',
            self::Drums => 'Schlagzeug',
            self::GuitarAmp => 'Gitarren-Amp',
            self::BassAmp => 'Bass-Amp',
            self::Keyboard => 'Keyboard',
            self::AcousticGuitar => 'Akustikgitarre',
            self::GuitarStand => 'Gitarrenständer',
            self::Mandolin => 'Mandoline',
            self::Banjo => 'Banjo',
            self::Violin => 'Geige / Violine',
            self::Flute => 'Flöte',
            self::Accordion => 'Akkordeon',
            self::Playback => 'Laptop / Playback',
            self::Wedge => 'Monitor-Wedge',
            self::Drumfill => 'Drumfill',
            self::Sidefill => 'Sidefill',
            self::DiBox => 'DI-Box',
            self::Power => 'Strom 230 V',
            self::Riser => 'Riser / Podest',
            self::Text => 'Textfeld',
        };
    }

    /**
     * Gruppe in der Symbol-Leiste.
     */
    public function group(): string
    {
        return match ($this) {
            self::Person, self::Microphone, self::InEar => 'Band & Gesang',
            self::Wedge, self::Drumfill, self::Sidefill => 'Monitoring',
            self::DiBox, self::Power, self::Riser, self::Text => 'Technik & Bühne',
            default => 'Instrumente',
        };
    }

    /**
     * Startgröße auf der Bühne in Zentimetern [Breite, Tiefe] – etwas größer als in echt, damit Schrift lesbar bleibt.
     * Beim Textfeld ist die Tiefe die Schriftgröße.
     *
     * @return array{0: int, 1: int}
     */
    public function size(): array
    {
        return match ($this) {
            self::Person => [70, 70],
            self::Microphone => [60, 60],
            self::InEar => [50, 44],
            self::Drums => [220, 179],
            self::GuitarAmp => [100, 56],
            self::BassAmp => [100, 70],
            self::Keyboard => [140, 44],
            self::AcousticGuitar, self::Mandolin, self::Banjo, self::Violin => [40, 70],
            self::GuitarStand => [36, 54],
            self::Flute => [90, 34],
            self::Accordion => [70, 43],
            self::Playback => [60, 47],
            self::Wedge => [80, 56],
            self::Drumfill => [100, 58],
            self::Sidefill => [55, 71],
            self::DiBox => [50, 33],
            self::Power => [40, 48],
            self::Riser => [200, 150],
            self::Text => [0, 24],
        };
    }
}
