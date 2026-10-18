<?php

namespace App\Services\BandManager;

use App\Actions\BandManager\Profile\ListProfileLinksAction;
use App\Actions\MediaLibrary\CreatePdfImageAction;
use App\Enums\BandManager\GigStatusEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
use App\Models\BandManager\GalleryImage;
use App\Models\BandManager\Gig;
use App\Models\BandManager\TeamMember;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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

    /** @var list<string> Lokale Kopien aus der Media Library – werden nach dem ZIP gelöscht */
    private array $temporaryFiles = [];

    public function __construct(private Pdf $pdf, private CreatePdfImageAction $createPdfImage) {}

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
        $profile = $band->profile;

        return [
            'bio_de' => trim((string) $profile->bio_de) !== '',
            'bio_en' => trim((string) $profile->bio_en) !== '',
            'links' => count(app(ListProfileLinksAction::class)->execute($profile)),
            'members' => $band->teamMembers()->where('type', TeamMember::TYPE_MEMBER)->count(),
            'images' => $band->galleryImages()->count(),
            'main' => $band->mainGalleryImage()->exists(),
            'riders' => $band->documents()->with('media')->where('type', Document::TYPE_RIDER)->latest()->latest('id')->get(),
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
        $images = $options['photos_pdf'] || $options['photos_files'] ? $band->galleryImages()->with('media')->ordered()->get() : new Collection;

        if ($images->isNotEmpty() && $options['photos_pdf']) {
            $photos = [];

            foreach ($images as $image) {
                if ($source = $this->pdfImage($image)) {
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
        if ($images->isNotEmpty() && $options['photos_files']) {
            $credits = [];

            foreach ($images->values() as $index => $image) {
                $original = $image->getFirstMedia(MediaCollectionEnum::BandPhotos->value);

                if ($original === null) {
                    continue;
                }

                $name = sprintf('%02d_%s', $index + 1, $this->slug($this->photoLabel($image, $texts).' '.$image->caption, 50))
                    .'.'.strtolower($original->extension);
                $name = $this->add($texts['dir_photos'].'/'.$name, path: $this->localCopy($original));

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
                if ($media = $rider->getFirstMedia(MediaCollectionEnum::Documents->value)) {
                    $name = $this->slug($rider->category?->label().' '.$rider->title, 70) ?: 'Rider';
                    $this->add($texts['dir_riders'].'/'.$name.'.pdf', path: $this->localCopy($media));
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
        $query = $band->gigs()->where('status', GigStatusEnum::Confirmed);

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
        $profile = $band->profile;
        $cover = null;

        if ($options['cover'] && ($main = $band->mainGalleryImage) && ($source = $this->pdfImage($main))) {
            $cover = $source + ['credit' => (string) $main->credit];
        }

        $members = [];

        if ($options['members']) {
            $people = $band->teamMembers()->with('media')->where('type', TeamMember::TYPE_MEMBER)->orderBy('first_name')->orderBy('last_name')->get();

            foreach ($people as $person) {
                $members[] = [
                    'name' => trim($person->first_name.' '.$person->last_name),
                    'stage' => (string) $person->stage_name,
                    'role' => (string) $person->position,
                    'bio' => $options['member_bios'] ? (string) $person->bio : '',
                    'photo' => $this->createPdfImage->execute($person->getFirstMedia(MediaCollectionEnum::MemberPhotos->value), MediaConversionEnum::Preview->value)['src'] ?? null,
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
                ? app(ListProfileLinksAction::class)->execute($profile)
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
     * Galeriebild (Vorschau) als data-URI für Dompdf.
     *
     * @return array{src: string, w: int, h: int}|null
     */
    private function pdfImage(GalleryImage $image): ?array
    {
        return $this->createPdfImage->execute($image->getFirstMedia(MediaCollectionEnum::BandPhotos->value), MediaConversionEnum::Preview->value);
    }

    /**
     * Lokale Kopie für ZipArchive – im Bucket gibt es keinen Dateipfad.
     */
    private function localCopy(Media $media): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'pk_media');
        file_put_contents($path, $media->stream());

        return $this->temporaryFiles[] = $path;
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

        $closed = $zip->close();

        foreach ($this->temporaryFiles as $temporaryFile) {
            @unlink($temporaryFile);
        }

        $this->temporaryFiles = [];

        if (! $closed) {
            throw new RuntimeException('ZIP-Datei konnte nicht geschrieben werden.');
        }

        return ['path' => $tmp, 'name' => "{$root}.zip", 'count' => count($this->files)];
    }
}
