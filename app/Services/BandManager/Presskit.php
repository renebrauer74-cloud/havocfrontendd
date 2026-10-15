<?php

namespace App\Services\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\BandProfile;
use App\Models\BandManager\Document;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Baut das Pressekit als ZIP:
 *   Pressekit_<Band>_<Datum>/01_Bandinfo_DE.pdf, 01_Bandinfo_EN.pdf, 02_Fotos.pdf, 03_Live-Termine.pdf, Fotos/…, Rider/…
 * Gagen, interne Notizen, Anfragen und private Daten (Adresse, Telefon, Geburtsdatum …) kommen NIE hinein.
 */
class Presskit
{
    public const PARTS = ['cover', 'bio', 'members', 'member_bios', 'links', 'contact',
        'photos_pdf', 'photos_files', 'gigs_upcoming', 'gigs_past'];

    private const INFO_PARTS = ['cover', 'bio', 'members', 'links', 'contact'];

    private const TEXTS = [
        'de' => [
            'presskit' => 'PRESSEKIT', 'presskit_title' => 'Pressekit', 'as_of' => 'Stand',
            'bio' => 'Biografie', 'lineup' => 'Besetzung', 'links' => 'Social Media & Streaming', 'contact' => 'Kontakt & Booking',
            'photos' => 'Fotos', 'photos_hint' => 'Alle Fotos in hoher Auflösung findest du im Ordner „Fotos“.', 'photo_main' => 'Hauptbild',
            'live' => 'Live-Termine', 'upcoming' => 'Kommende Shows', 'past' => 'Vergangene Shows', 'months' => 'Monate',
            'date' => 'Datum', 'city' => 'Ort', 'venue' => 'Location', 'event' => 'Veranstaltung', 'no_gigs' => 'Aktuell keine Termine.',
            'file_info' => 'Bandinfo', 'file_photos' => 'Fotos', 'file_gigs' => 'Live-Termine',
            'dir_photos' => 'Fotos', 'dir_riders' => 'Rider', 'credits' => 'Bildnachweise', 'quotes' => ['„', '“'],
            'categories' => ['band' => 'Bandfoto', 'live' => 'Live', 'video' => 'Musikvideo', 'other' => 'Foto'],
        ],
        'en' => [
            'presskit' => 'PRESS KIT', 'presskit_title' => 'Press kit', 'as_of' => 'As of',
            'bio' => 'Biography', 'lineup' => 'Line-up', 'links' => 'Social media & streaming', 'contact' => 'Contact & booking',
            'photos' => 'Photos', 'photos_hint' => 'All photos in high resolution can be found in the folder "Photos".', 'photo_main' => 'Main photo',
            'live' => 'Live dates', 'upcoming' => 'Upcoming shows', 'past' => 'Past shows', 'months' => 'months',
            'date' => 'Date', 'city' => 'City', 'venue' => 'Venue', 'event' => 'Event', 'no_gigs' => 'No dates at the moment.',
            'file_info' => 'Band-Info', 'file_photos' => 'Photos', 'file_gigs' => 'Live-Dates',
            'dir_photos' => 'Photos', 'dir_riders' => 'Riders', 'credits' => 'Photo-Credits', 'quotes' => ['“', '”'],
            'categories' => ['band' => 'Band photo', 'live' => 'Live', 'video' => 'Music video', 'other' => 'Photo'],
        ],
    ];

    /** @var array<string, array{data?: string, path?: string}> Name im ZIP => Inhalt oder Pfad */
    private array $files = [];

    public function __construct(private Pdf $pdf) {}

    /**
     * Fehlende Server-Voraussetzungen (leer = alles ok).
     *
     * @return list<string>
     */
    public function requirements(): array
    {
        return class_exists(ZipArchive::class) ? [] : [
            'Die PHP-Erweiterung „zip“ fehlt auf dem Server (z. B. „apt install php-zip“ bzw. extension=zip in der php.ini).',
        ];
    }

    /**
     * Auswahl aus dem Formular: inc[] (Teile), lang[] (de/en), riders[] (Dokument-IDs).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function options(array $input): array
    {
        $included = array_flip(array_filter((array) ($input['inc'] ?? []), 'is_string'));
        $languages = array_values(array_intersect(['de', 'en'], array_filter((array) ($input['lang'] ?? []), 'is_string')));
        $riders = array_values(array_unique(array_filter(array_map('intval', array_filter((array) ($input['riders'] ?? []), 'is_scalar')))));

        $options = ['langs' => $languages, 'riders' => $riders];

        foreach (self::PARTS as $part) {
            $options[$part] = isset($included[$part]);
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function validationError(array $options): ?string
    {
        $info = $this->wantsInfo($options);

        if (! $info && ! $options['photos_pdf'] && ! $options['photos_files'] && ! $options['riders'] && ! $options['gigs_upcoming'] && ! $options['gigs_past']) {
            return 'Bitte mindestens einen Inhalt auswählen.';
        }

        if ($info && ! $options['langs']) {
            return 'Bitte mindestens eine Sprache für die Bandinfo wählen.';
        }

        return null;
    }

    /**
     * Was ist für diese Band vorhanden? (für Häkchen und Hinweise im Formular)
     *
     * @return array<string, mixed>
     */
    public function inventory(Band $band): array
    {
        $profile = $band->profileOrNew();

        return [
            'bio_de' => trim((string) $profile->bio_de) !== '',
            'bio_en' => trim((string) $profile->bio_en) !== '',
            'links' => count($profile->linkList()),
            'members' => $band->teamMembers()->where('type', TeamMember::TYPE_MEMBER)->count(),
            'images' => $band->galleryImages()->count(),
            'main' => GalleryImage::mainFor($band) !== null,
            'riders' => $band->documents()->where('type', Document::TYPE_RIDER)->latest()->latest('id')->get(),
            'upcoming' => $this->gigs($band, true)->count(),
            'past' => $this->gigs($band, false)->count(),
            'contact' => (string) (config('band-manager.exports.contact.name') ?: 'Agentur'),
            'pastMonths' => $this->pastMonths(),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{path: string, name: string, count: int}
     */
    public function build(Band $band, array $options): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $this->files = [];
        $languages = $options['langs'] ?: ['de'];
        $texts = self::TEXTS[$languages[0]];   // Sprache für Fotos, Termine und Ordnernamen
        $number = 1;

        // ---------- 1. Bandinfo (pro Sprache) ----------
        if ($this->wantsInfo($options)) {
            $data = $this->infoData($band, $options);

            foreach ($languages as $language) {
                $languageTexts = self::TEXTS[$language];
                $this->add(
                    sprintf('%02d_%s_%s.pdf', $number, $languageTexts['file_info'], strtoupper($language)),
                    $this->pdf->render($this->renderBandInfo($band, $options, $language, $data), $this->footer($band, $languageTexts)),
                );
            }

            $number++;
        }

        // ---------- 2. Fotos (PDF-Übersicht) ----------
        $images = $options['photos_pdf'] || $options['photos_files'] ? $band->galleryImages()->ordered()->get() : new Collection;

        if ($images->isNotEmpty() && $options['photos_pdf']) {
            $photos = [];

            foreach ($images as $image) {
                if ($source = $image->pdfSource()) {
                    $photos[] = $source + [
                        'main' => $image->is_main,
                        'label' => $this->photoLabel($image, $texts),
                        'caption' => (string) $image->caption,
                        'credit' => (string) $image->credit,
                    ];
                }
            }

            if ($photos !== []) {
                $html = view('band-manager.presskit.pdf.photos', [
                    'band' => $band, 't' => $texts, 'lang' => $languages[0], 'logo' => $this->pdf->logo(),
                    'photos' => $photos, 'hint' => $options['photos_files'],
                ])->render();
                $this->add(sprintf('%02d_%s.pdf', $number++, $texts['file_photos']), $this->pdf->render($html, $this->footer($band, $texts)));
            }
        }

        // ---------- 3. Live-Termine ----------
        if ($options['gigs_upcoming'] || $options['gigs_past']) {
            $this->add(
                sprintf('%02d_%s.pdf', $number++, $texts['file_gigs']),
                $this->pdf->render($this->renderLiveDates($band, $options, $languages[0]), $this->footer($band, $texts)),
            );
        }

        // ---------- 4. Fotos als Originaldateien ----------
        $disk = Storage::disk('band-manager');

        if ($images->isNotEmpty() && $options['photos_files']) {
            $credits = [];

            foreach ($images->values() as $index => $image) {
                $path = $image->storagePath(preview: false);

                if (! $disk->exists($path)) {
                    continue;
                }

                $name = sprintf('%02d_%s', $index + 1, $this->slug($this->photoLabel($image, $texts).' '.$image->caption, 50))
                    .'.'.strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $name = $this->add($texts['dir_photos'].'/'.$name, path: $disk->path($path));

                if ($image->credit) {
                    $credits[] = basename($name).': © '.$image->credit;
                }
            }

            if ($credits !== []) {
                $this->add($texts['dir_photos'].'/'.$texts['credits'].'.txt', implode("\r\n", $credits)."\r\n");
            }
        }

        // ---------- 5. Rider (vorhandene PDFs, nur eigene) ----------
        if ($options['riders'] !== []) {
            $riders = Document::query()
                ->whereKey($options['riders'])
                ->where('band_id', $band->id)
                ->where('type', Document::TYPE_RIDER)
                ->get();

            foreach ($riders as $rider) {
                if ($disk->exists($rider->storagePath())) {
                    $name = $this->slug($rider->categoryLabel().' '.$rider->title, 70) ?: 'Rider';
                    $this->add($texts['dir_riders'].'/'.$name.'.pdf', path: $disk->path($rider->storagePath()));
                }
            }
        }

        if ($this->files === []) {
            throw new RuntimeException('Für die gewählten Inhalte gibt es noch keine Daten – das Pressekit wäre leer.');
        }

        return $this->zip($band);
    }

    /**
     * HTML der Bandinfo-PDF in einer Sprache.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>|null  $data  bereits gesammelte Daten (spart Abfragen bei mehreren Sprachen)
     */
    public function renderBandInfo(Band $band, array $options, string $language, ?array $data = null): string
    {
        return view('band-manager.presskit.pdf.bandinfo', ($data ?? $this->infoData($band, $options)) + [
            't' => self::TEXTS[$language],
            'lang' => $language,
        ])->render();
    }

    /**
     * HTML der Live-Termine-PDF (nur bestätigte Gigs).
     *
     * @param  array<string, mixed>  $options
     */
    public function renderLiveDates(Band $band, array $options, string $language): string
    {
        return view('band-manager.presskit.pdf.gigs', [
            'band' => $band, 't' => self::TEXTS[$language], 'lang' => $language, 'logo' => $this->pdf->logo(),
            'upcoming' => $options['gigs_upcoming'] ? $this->gigRows($this->gigs($band, true), $language) : null,
            'past' => $options['gigs_past'] ? $this->gigRows($this->gigs($band, false), $language) : null,
            'months' => $this->pastMonths(),
        ])->render();
    }

    public function slug(string $text, int $max = 60): string
    {
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss']);
        $text = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', Str::ascii($text)), '-');

        return rtrim(substr($text, 0, $max), '-');
    }

    /**
     * Nur BESTÄTIGTE Gigs – keine Anfragen, keine Gagen.
     *
     * @return Collection<int, Gig>
     */
    private function gigs(Band $band, bool $upcoming): Collection
    {
        $today = CarbonImmutable::today();
        $query = $band->gigs()->where('status', Gig::STATUS_CONFIRMED);

        if ($upcoming) {
            return $query->where('date', '>=', $today->format('Y-m-d'))->orderBy('date')->get();
        }

        return $query
            ->where('date', '<', $today->format('Y-m-d'))
            ->where('date', '>=', $today->subMonths($this->pastMonths())->format('Y-m-d'))
            ->orderByDesc('date')
            ->get();
    }

    /**
     * @param  Collection<int, Gig>  $gigs
     * @return list<array{date: string, city: string, venue: string, event: string}>
     */
    private function gigRows(Collection $gigs, string $language): array
    {
        return $gigs->map(fn (Gig $gig): array => [
            'date' => $this->dateLabel($gig->date, $language),
            'city' => (string) $gig->city,
            'venue' => (string) $gig->venue,
            'event' => $gig->title,
        ])->values()->all();
    }

    private function dateLabel(string $date, string $language): string
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        return $language === 'de'
            ? ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'][$day->dayOfWeek].', '.$day->format('d.m.Y')
            : $day->format('D, M j, Y');
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function infoData(Band $band, array $options): array
    {
        $profile = $band->profileOrNew();
        $cover = null;

        if ($options['cover'] && ($main = GalleryImage::mainFor($band)) && ($source = $main->pdfSource())) {
            $cover = $source + ['credit' => (string) $main->credit];
        }

        $members = [];

        if ($options['members']) {
            $people = $band->teamMembers()->where('type', TeamMember::TYPE_MEMBER)->orderBy('first_name')->orderBy('last_name')->get();

            foreach ($people as $person) {
                $members[] = [
                    'name' => $person->fullName(),
                    'stage' => (string) $person->stage_name,
                    'role' => (string) $person->position,
                    'bio' => $options['member_bios'] ? (string) $person->bio : '',
                    'photo' => $this->imageUri($person->photoPath()),
                ];
            }
        }

        return [
            'band' => $band,
            'logo' => $this->pdf->logo(),
            'bio' => $options['bio'] ? ['de' => (string) $profile->bio_de, 'en' => (string) $profile->bio_en] : [],
            'cover' => $cover,
            'members' => $members,
            'links' => $options['links']
                ? array_map(fn (array $link): array => $link + ['display' => BandProfile::display($link['url'])], $profile->linkList())
                : [],
            'contact' => $options['contact'] ? array_filter((array) config('band-manager.exports.contact')) : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function wantsInfo(array $options): bool
    {
        foreach (self::INFO_PARTS as $part) {
            if (! empty($options[$part])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $texts
     */
    private function photoLabel(GalleryImage $image, array $texts): string
    {
        return $image->is_main ? $texts['photo_main'] : ($texts['categories'][$image->category] ?? $texts['categories']['other']);
    }

    /**
     * @param  array<string, mixed>  $texts
     */
    private function footer(Band $band, array $texts): string
    {
        return $band->name.' · '.$texts['presskit_title'].' · '.$texts['as_of'].' '.now()->format('d.m.Y').' · {PAGE_NUM}/{PAGE_COUNT}';
    }

    private function pastMonths(): int
    {
        return max(1, (int) config('band-manager.exports.past_months'));
    }

    /**
     * Bild vom Disk als data-URI für Dompdf (nur JPG/PNG, max. 5 MB).
     */
    private function imageUri(?string $path): ?string
    {
        $disk = Storage::disk('band-manager');

        if ($path === null || ! $disk->exists($path) || $disk->size($path) > 5 * 1048576) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };

        return $mime ? 'data:'.$mime.';base64,'.base64_encode((string) $disk->get($path)) : null;
    }

    /**
     * Datei ins ZIP aufnehmen – liefert den (ggf. eindeutig gemachten) Namen.
     */
    private function add(string $name, ?string $data = null, ?string $path = null): string
    {
        $base = $name;

        for ($i = 2; isset($this->files[$name]); $i++) {
            $name = (string) preg_replace('/(\.[A-Za-z0-9]+)$/', "_{$i}$1", $base);
        }

        $this->files[$name] = $path !== null ? ['path' => $path] : ['data' => (string) $data];

        return $name;
    }

    /**
     * @return array{path: string, name: string, count: int}
     */
    private function zip(Band $band): array
    {
        $root = 'Pressekit_'.($this->slug($band->name) ?: 'Band').'_'.now()->format('Y-m-d');
        $tmp = tempnam(sys_get_temp_dir(), 'pk_');
        $zip = new ZipArchive;

        if ($tmp === false || $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ZIP-Datei konnte nicht angelegt werden (Temp-Ordner prüfen).');
        }

        foreach ($this->files as $name => $file) {
            if (isset($file['path'])) {
                $zip->addFile($file['path'], "{$root}/{$name}");

                // Bilder & PDFs sind schon komprimiert → unverändert ablegen (viel schneller)
                if (preg_match('/\.(jpe?g|png|webp|pdf)$/i', $name)) {
                    $zip->setCompressionName("{$root}/{$name}", ZipArchive::CM_STORE);
                }
            } else {
                $zip->addFromString("{$root}/{$name}", $file['data']);
            }
        }

        if (! $zip->close()) {
            throw new RuntimeException('ZIP-Datei konnte nicht geschrieben werden.');
        }

        return ['path' => $tmp, 'name' => "{$root}.zip", 'count' => count($this->files)];
    }
}
