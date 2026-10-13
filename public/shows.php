<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$shows = agency_shows()->all();

$pageTitle = 'HAVOC AGENCY | SHOWS';
$layout = 'editorial';
$styles = ['shows.css'];
require BASE_PATH . '/templates/header.php';
?>

    <div class="content-container">
        <main>
            <h1 class="page-title">AGENCY SHOWS //</h1>
            <div class="show-list">
<?php if (!$shows): ?>
                <p class="list-empty">Aktuell keine exklusiven Shows geplant.</p>
<?php endif; ?>
<?php foreach ($shows as $show): ?>
                <a href="<?= e(url('show-detail.php?id=' . $show['id'])) ?>" class="show-row">
                    <div class="show-date"><?= e($show['date']) ?></div>
                    <div class="show-title"><?= e($show['title']) ?></div>
                    <div class="show-location"><?= e($show['venue']) ?> // <?= e($show['city']) ?></div>
                    <div>
<?php if (!empty($show['ticket_url'])): ?>
                        <span class="ticket-btn" role="link" tabindex="0" data-external-link="<?= e(safe_link($show['ticket_url'])) ?>">TICKETS //</span>
<?php else: ?>
                        —
<?php endif; ?>
                    </div>
                </a>
<?php endforeach; ?>
            </div>
        </main>
    </div>

<?php require BASE_PATH . '/templates/footer.php'; ?>
