<?php
declare(strict_types=1);

use Havoc\Repository\AgencyShowRepository;
use Havoc\Repository\ArtistRepository;
use Havoc\Repository\GalleryRepository;
use Havoc\Repository\MessageRepository;
use Havoc\Repository\NewsRepository;
use Havoc\Repository\TeamRepository;
use Havoc\Repository\TourDateRepository;
use Havoc\Storage\Database;

// --- Konfiguration --------------------------------------------------------

/** Liest einen Config-Wert per Punkt-Notation, z.B. config('admin.username'). */
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['havoc_config'] ?? [];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

// --- Ausgabe ---------------------------------------------------------------

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function nl2br_e(?string $value): string
{
    return nl2br(e($value), false);
}

/** Interner Link relativ zum Webroot (berücksichtigt base_url). */
function url(string $path = ''): string
{
    return rtrim((string) config('base_url', ''), '/') . '/' . ltrim($path, '/');
}

/** URL für gespeicherte Bilder: lokale Upload-Pfade oder externe http(s)-URLs. */
function media_url(?string $path, string $fallback = ''): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return $fallback;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return url($path);
}

/** Externe Links aus dem Admin absichern: nur http(s) und mailto, sonst '#'. */
function safe_link(?string $link): string
{
    $link = trim((string) $link);
    if ($link === '') {
        return '#';
    }
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $link)) {
        return preg_match('#^(https?://|mailto:)#i', $link) ? $link : '#';
    }
    return 'https://' . ltrim($link, '/');
}

/** Platzhalterbild als Data-URI, wenn kein Bild hinterlegt ist. */
function placeholder_image(int $width, int $height, string $label, int $fontSize = 16): string
{
    $svg = sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d">'
        . '<rect width="%1$d" height="%2$d" fill="#000"/>'
        . '<text x="50%%" y="50%%" font-family="Arial" font-size="%3$d" font-weight="900" fill="#fff" '
        . 'text-anchor="middle" dominant-baseline="middle">%4$s</text></svg>',
        $width,
        $height,
        $fontSize,
        htmlspecialchars($label, ENT_XML1)
    );
    return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}

// --- Datum -----------------------------------------------------------------

/** Versucht Freitext-Daten wie "24.05.2026" zu parsen – für die Sortierung. */
function parse_show_date(?string $date): ?int
{
    $date = trim((string) $date);
    foreach (['!d.m.Y', '!j.n.Y', '!d.m.y', '!Y-m-d'] as $format) {
        $parsed = DateTimeImmutable::createFromFormat($format, $date);
        if ($parsed !== false && DateTimeImmutable::getLastErrors() === false) {
            return $parsed->getTimestamp();
        }
    }
    return null;
}

/** Sortiert Datensätze chronologisch nach ihrem 'date'-Feld; Unparsbares landet am Ende. */
function sort_by_show_date(array $rows): array
{
    usort($rows, static function (array $a, array $b): int {
        $ta = parse_show_date($a['date'] ?? '');
        $tb = parse_show_date($b['date'] ?? '');
        if ($ta === $tb) {
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        }
        if ($ta === null) {
            return 1;
        }
        if ($tb === null) {
            return -1;
        }
        return $ta <=> $tb;
    });
    return $rows;
}

function format_date_de(?string $isoDateTime): string
{
    $timestamp = strtotime((string) $isoDateTime);
    return $timestamp ? date('d.m.Y', $timestamp) : '';
}

// --- Request / Response ----------------------------------------------------

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim(mb_scrub($value, 'UTF-8')) : '';
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function not_found(string $message = 'Seite nicht gefunden.'): never
{
    http_response_code(404);
    exit(e($message));
}

/** Einmal-Nachricht über einen Redirect hinweg. Ohne $message: lesen und löschen. */
function flash(string $key, ?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = ['type' => $type, 'message' => $message];
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

// --- CSRF ------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Formular abgelaufen. Bitte Seite neu laden und erneut absenden.');
    }
}

// --- Datenzugriff ----------------------------------------------------------

function db(): Database
{
    static $db = null;
    return $db ??= new Database((array) config('database', []), BASE_PATH . '/storage/data');
}

function artists(): ArtistRepository
{
    static $repo = null;
    return $repo ??= new ArtistRepository(db()->table('artists'));
}

function tour_dates(): TourDateRepository
{
    static $repo = null;
    return $repo ??= new TourDateRepository(db()->table('tour_dates'));
}

function gallery(): GalleryRepository
{
    static $repo = null;
    return $repo ??= new GalleryRepository(db()->table('gallery'));
}

function news(): NewsRepository
{
    static $repo = null;
    return $repo ??= new NewsRepository(db()->table('news'));
}

function team(): TeamRepository
{
    static $repo = null;
    return $repo ??= new TeamRepository(db()->table('team'));
}

function agency_shows(): AgencyShowRepository
{
    static $repo = null;
    return $repo ??= new AgencyShowRepository(db()->table('agency_shows'));
}

function messages(): MessageRepository
{
    static $repo = null;
    return $repo ??= new MessageRepository(db()->table('messages'));
}
