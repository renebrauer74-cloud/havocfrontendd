<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$roster = artists()->all();

$pageTitle = 'HAVOC AGENCY | Roster';
$styles = ['roster.css'];
require BASE_PATH . '/templates/header.php';
?>

    <main>
        <h1 class="page-title">Agency<span> Roster //</span></h1>

        <section class="roster-section">
            <div class="grid-container">
<?php if (!$roster): ?>
                <div class="empty-state roster-empty">NO ARTISTS IN ROSTER YET //</div>
<?php endif; ?>
<?php foreach ($roster as $artist): ?>
                <a class="artist-card" href="<?= e(url('artist.php?id=' . $artist['id'])) ?>">
                    <div class="poster-frame">
                        <img class="artist-img" src="<?= e(media_url($artist['image_url'] ?? '', url('assets/img/havoc-logo.jpeg'))) ?>" alt="<?= e($artist['name']) ?>">
                    </div>
                    <div class="artist-name"><?= e($artist['name']) ?></div>
                    <div class="artist-genre"><?= e($artist['genre'] ?: 'Alternative') ?></div>
                </a>
<?php endforeach; ?>
            </div>
        </section>
    </main>

<?php require BASE_PATH . '/templates/footer.php'; ?>
