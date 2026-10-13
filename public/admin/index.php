<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';

use Havoc\Auth;
use Havoc\Repository\GalleryRepository;
use Havoc\Upload;

Auth::requireLogin();

$sections = [
    'artists'  => 'Künstler',
    'dates'    => 'Tourdaten',
    'gallery'  => 'Bildergalerie',
    'news'     => 'News',
    'team'     => 'Team',
    'shows'    => 'Shows (Agentur)',
    'messages' => 'Anfragen',
];

// Verwaltbare Datensätze: Repository, Formularfelder, Pflichtfelder und ggf. Upload-Ordner für Bilder.
$entities = [
    'artist' => [
        'repo' => artists(), 'section' => 'artists', 'label' => 'Künstlerprofil',
        'fields' => ['name', 'genre', 'bio', 'bio_en', 'spotify_url', 'youtube_url', 'website_url', 'instagram_url', 'tiktok_url'],
        'required' => ['name'], 'images' => 'artists',
    ],
    'date' => [
        'repo' => tour_dates(), 'section' => 'dates', 'label' => 'Tourtermin',
        'fields' => ['artist_id', 'date', 'city', 'venue', 'link'],
        'required' => ['artist_id', 'date', 'city', 'venue'],
    ],
    'gallery' => [
        'repo' => gallery(), 'section' => 'gallery', 'label' => 'Galeriebild',
        'fields' => ['artist_id'], 'required' => ['artist_id'], 'images' => 'gallery',
    ],
    'news' => [
        'repo' => news(), 'section' => 'news', 'label' => 'News-Beitrag',
        'fields' => ['title', 'text'], 'required' => ['title', 'text'], 'images' => 'news',
    ],
    'team' => [
        'repo' => team(), 'section' => 'team', 'label' => 'Teammitglied',
        'fields' => ['name', 'phone', 'email', 'hobbies', 'bio'], 'required' => ['name', 'email'], 'images' => 'team',
    ],
    'show' => [
        'repo' => agency_shows(), 'section' => 'shows', 'label' => 'Agency-Show',
        'fields' => ['title', 'date', 'city', 'venue', 'ticket_url', 'info_text'],
        'required' => ['title', 'date', 'city', 'venue'], 'images' => 'shows',
    ],
    'message' => [
        'repo' => messages(), 'section' => 'messages', 'label' => 'Anfrage',
        'fields' => [], 'required' => [],
    ],
];

function save_entity(string $key, array $entity): string
{
    $id = (int) input('id');
    $existing = $id ? $entity['repo']->find($id) : null;
    if ($id && !$existing) {
        throw new RuntimeException('Datensatz nicht gefunden.');
    }

    $data = [];
    foreach ($entity['fields'] as $field) {
        $data[$field] = input($field);
    }
    foreach ($entity['required'] as $field) {
        if ($data[$field] === '') {
            throw new RuntimeException('Bitte alle Pflichtfelder ausfüllen.');
        }
    }

    if (array_key_exists('artist_id', $data)) {
        $data['artist_id'] = (int) $data['artist_id'];
        if (!artists()->find($data['artist_id'])) {
            throw new RuntimeException('Bitte eine gültige Band auswählen.');
        }
    }
    if ($key === 'team' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Bitte eine gültige E-Mail-Adresse angeben.');
    }
    if ($key === 'gallery' && count(gallery()->forArtist($data['artist_id'])) >= GalleryRepository::MAX_PER_ARTIST) {
        throw new RuntimeException('Maximal ' . GalleryRepository::MAX_PER_ARTIST . ' Galeriebilder pro Band.');
    }

    if (isset($entity['images'])) {
        $image = Upload::image('image', $entity['images']);
        if ($image !== null) {
            $data['image_url'] = $image;
            Upload::delete($existing['image_url'] ?? null);
        } elseif ($key === 'gallery') {
            throw new RuntimeException('Bitte ein Bild auswählen.');
        }
    }

    if ($existing) {
        $entity['repo']->update($id, $data);
        return $entity['label'] . ' aktualisiert //';
    }
    $entity['repo']->create($data);
    return $entity['label'] . ' gespeichert //';
}

function delete_entity(string $key, array $entity): string
{
    $id = (int) input('id');
    $record = $entity['repo']->find($id) ?? throw new RuntimeException('Datensatz nicht gefunden.');

    if ($key === 'artist') {
        tour_dates()->deleteForArtist($id);
        foreach (gallery()->forArtist($id) as $photo) {
            Upload::delete($photo['image_url'] ?? null);
        }
        gallery()->deleteForArtist($id);
    }

    $entity['repo']->delete($id);
    Upload::delete($record['image_url'] ?? null);
    return $entity['label'] . ' gelöscht //';
}

/** Kleines Lösch-Formular mit Sicherheitsabfrage für die Verwaltungslisten. */
function delete_button(string $entity, int $id, string $confirm, string $label = 'LÖSCHEN', array $extra = []): string
{
    $html = '<form method="post" action="' . e(url('admin/')) . '" data-confirm="' . e($confirm) . '">'
        . csrf_field()
        . '<input type="hidden" name="entity" value="' . e($entity) . '">'
        . '<input type="hidden" name="action" value="delete">'
        . '<input type="hidden" name="id" value="' . $id . '">';
    foreach ($extra as $name => $value) {
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e((string) $value) . '">';
    }
    return $html . '<button type="submit" class="delete-btn">' . e($label) . '</button></form>';
}

// --- POST: Speichern / Löschen, danach Redirect (PRG) ----------------------
if (is_post()) {
    verify_csrf();

    $entityKey = input('entity');
    $entity = $entities[$entityKey] ?? null;
    if (!$entity) {
        http_response_code(400);
        exit('Unbekannter Datentyp.');
    }

    $target = 'admin/?section=' . $entity['section'];
    try {
        $message = match (input('action')) {
            'save'   => save_entity($entityKey, $entity),
            'delete' => delete_entity($entityKey, $entity),
            default  => throw new RuntimeException('Unbekannte Aktion.'),
        };
        flash('admin', $message);
    } catch (RuntimeException $exception) {
        flash('admin', $exception->getMessage(), 'error');
        if (input('action') === 'save' && (int) input('id')) {
            $target .= '&edit=' . (int) input('id');
        }
    }

    if ($entityKey === 'gallery' && (int) input('artist_id')) {
        $target .= '&artist=' . (int) input('artist_id');
    }
    redirect($target);
}

// --- GET: Dashboard rendern ------------------------------------------------
$section = (string) ($_GET['section'] ?? 'artists');
if (!isset($sections[$section])) {
    $section = 'artists';
}

$editableBySection = ['artists' => 'artist', 'dates' => 'date', 'news' => 'news', 'team' => 'team', 'shows' => 'show'];
$editing = null;
if (isset($editableBySection[$section], $_GET['edit'])) {
    $editing = $entities[$editableBySection[$section]]['repo']->find((int) $_GET['edit']);
}

$flash = flash('admin');
$allArtists = artists()->allAlphabetical();

/** Escapter Feldwert des gerade bearbeiteten Datensatzes (leer beim Anlegen). */
$val = static fn (string $field): string => e((string) ($editing[$field] ?? ''));

require BASE_PATH . '/templates/admin/layout.php';
