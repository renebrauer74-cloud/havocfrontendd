<?php
require dirname(__DIR__) . '/src/bootstrap.php';

// Bands alphabetisch, jeweils mit ihren Terminen – Bands ohne Termine fallen raus.
$blocks = [];
foreach (artists()->allAlphabetical() as $artist) {
    $dates = tour_dates()->forArtist($artist['id']);
    if ($dates) {
        $blocks[] = ['artist' => $artist, 'dates' => $dates];
    }
}

$pageTitle = 'HAVOC AGENCY | Live Calendar';
$styles = ['kalender.css'];
require BASE_PATH . '/templates/header.php';
?>

    <main>
        <h1 class="calendar-title">Roster Live<span> Calendar //</span></h1>

<?php if (!$blocks): ?>
        <div class="empty-state calendar-empty">Aktuell sind agenturweit keine Tourdaten eingetragen.</div>
<?php endif; ?>
<?php foreach ($blocks as $block): ?>
        <div class="artist-block">
            <div class="artist-block-header">
                <a href="<?= e(url('artist.php?id=' . $block['artist']['id'])) ?>"><?= e($block['artist']['name']) ?></a>
                <span class="artist-block-genre"><?= e($block['artist']['genre'] ?: 'Alternative') ?></span>
            </div>
            <div class="calendar-table">
<?php foreach ($block['dates'] as $show): ?>
                <div class="calendar-row">
                    <div class="date-col"><?= e($show['date']) ?></div>
                    <div class="show-col"><?= e($show['city']) ?> <span class="separator">//</span> <?= e($show['venue']) ?></div>
                    <a href="<?= e(safe_link($show['link'] ?? '')) ?>" target="_blank" rel="noopener" class="ticket-btn">Tickets</a>
                </div>
<?php endforeach; ?>
            </div>
        </div>
<?php endforeach; ?>
    </main>

<?php require BASE_PATH . '/templates/footer.php'; ?>
