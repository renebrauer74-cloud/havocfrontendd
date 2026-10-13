<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$artist = artists()->find((int) ($_GET['id'] ?? 0));
if (!$artist) {
    not_found('Künstler nicht gefunden.');
}

$photos = gallery()->forArtist($artist['id']);
$dates = tour_dates()->forArtist($artist['id']);

$links = array_filter(array_map('safe_link', [
    'Official Website'  => $artist['website_url'] ?? '',
    'Listen on Spotify' => $artist['spotify_url'] ?? '',
    'Watch on YouTube'  => $artist['youtube_url'] ?? '',
    'Instagram'         => $artist['instagram_url'] ?? '',
    'TikTok'            => $artist['tiktok_url'] ?? '',
]), static fn (string $href): bool => $href !== '#');

$pageTitle = $artist['name'] . ' | HAVOC AGENCY';
$styles = ['artist.css'];
require BASE_PATH . '/templates/header.php';
?>

    <main>
        <section class="artist-hero">
            <div class="hero-image-container">
<?php if (!empty($artist['image_url'])): ?>
                <img src="<?= e(media_url($artist['image_url'])) ?>" alt="<?= e($artist['name']) ?>">
<?php endif; ?>
            </div>
            <div class="artist-header-box">
                <h1 class="artist-main-name"><?= e($artist['name']) ?></h1>
                <div class="artist-main-genre"><?= e($artist['genre'] ?: 'Alternative') ?></div>
            </div>
        </section>

        <!-- BIO & LINKS MIT SPRACH-TOGGLE -->
        <section class="profile-grid">
            <div class="bio-box" data-lang-switch>
                <div class="lang-toggle">
                    <button type="button" class="lang-btn is-active" data-lang="de">DE</button>
                    <button type="button" class="lang-btn" data-lang="en">EN</button>
                </div>
                <div data-lang-content="de">
<?php if (!empty($artist['bio'])): ?>
                    <?= nl2br_e($artist['bio']) ?>
<?php else: ?>
                    <span class="bio-empty">Bislang ist noch kein Infotext für diese Band hinterlegt.</span>
<?php endif; ?>
                </div>
                <div data-lang-content="en" hidden>
<?php if (!empty($artist['bio_en'])): ?>
                    <?= nl2br_e($artist['bio_en']) ?>
<?php else: ?>
                    <span class="bio-empty">No biography available for this artist yet.</span>
<?php endif; ?>
                </div>
            </div>

            <div class="links-box">
<?php foreach ($links as $label => $href): ?>
                <a href="<?= e($href) ?>" target="_blank" rel="noopener" class="media-btn"><?= e($label) ?></a>
<?php endforeach; ?>
<?php if (!$links): ?>
                <div class="empty-state">Keine Links hinterlegt</div>
<?php endif; ?>
            </div>
        </section>

<?php if ($photos): ?>
        <!-- LIVE-GALERIE -->
        <section class="artist-gallery-section">
            <div class="section-title">Live Gallery //</div>
            <div class="gallery-grid">
<?php foreach ($photos as $photo): ?>
                <a class="gallery-item" href="<?= e(media_url($photo['image_url'])) ?>" target="_blank">
                    <img src="<?= e(media_url($photo['image_url'])) ?>" alt="Live Photo <?= e($artist['name']) ?>">
                </a>
<?php endforeach; ?>
            </div>
        </section>
<?php endif; ?>

        <!-- UPCOMING SHOWS -->
        <section class="artist-shows">
            <div class="section-title">Upcoming Shows</div>
            <div class="artist-shows-list">
<?php if (!$dates): ?>
                <div class="empty-state shows-empty">Aktuell keine kommenden Tourdaten für diese Band eingetragen.</div>
<?php endif; ?>
<?php foreach ($dates as $show): ?>
                <div class="ticket-row">
                    <div class="date-info"><?= e($show['date']) ?></div>
                    <div class="show-info"><?= e($show['city']) ?> <span class="separator">//</span> <?= e($show['venue']) ?></div>
                    <a href="<?= e(safe_link($show['link'] ?? '')) ?>" target="_blank" rel="noopener" class="ticket-btn">Tickets</a>
                </div>
<?php endforeach; ?>
            </div>
        </section>
    </main>

<?php require BASE_PATH . '/templates/footer.php'; ?>
