<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$show = agency_shows()->find((int) ($_GET['id'] ?? 0));
if (!$show) {
    not_found('Show nicht gefunden.');
}

$pageTitle = 'HAVOC AGENCY | ' . $show['title'];
$layout = 'editorial';
$styles = ['show-detail.css'];
$backLink = ['shows.php', '← ZURÜCK ZUR ÜBERSICHT'];
require BASE_PATH . '/templates/header.php';
?>

    <div class="content-container">
        <main>
            <div class="detail-grid">
                <div class="poster-frame">
                    <img src="<?= e(media_url($show['image_url'] ?? '', placeholder_image(300, 400, 'HAVOC SHOW'))) ?>" alt="<?= e($show['title']) ?>">
                </div>
                <div class="info-pane">
                    <h1><?= e($show['title']) ?></h1>
                    <div class="meta-pane">
                        <div><strong>WANN //</strong> <?= e($show['date']) ?></div>
                        <div><strong>WO //</strong> <?= e($show['venue']) ?> — <?= e($show['city']) ?></div>
                    </div>
                    <p class="long-text"><?= !empty($show['info_text']) ? nl2br_e($show['info_text']) : 'Keine weiteren Details hinterlegt.' ?></p>
<?php if (!empty($show['ticket_url'])): ?>
                    <div class="ticket-action">
                        <a href="<?= e(safe_link($show['ticket_url'])) ?>" target="_blank" rel="noopener" class="ticket-btn-large">DIREKT TICKETS SICHERN //</a>
                    </div>
<?php endif; ?>
                </div>
            </div>
        </main>
    </div>

<?php require BASE_PATH . '/templates/footer.php'; ?>
