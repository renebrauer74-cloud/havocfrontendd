<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$roster = artists()->all();
$newsList = news()->latest();
$latestNews = array_slice($newsList, 0, 2);
$archiveNews = array_slice($newsList, 2);
$upcomingDates = array_slice(tour_dates()->allWithArtist(artists()), 0, 4);

$pageTitle = 'HAVOC AGENCY | Booking & Management';
$styles = ['index.css'];
require BASE_PATH . '/templates/header.php';
?>

    <!-- FULL-WIDTH BAND KARUSSELL (Liste doppelt für die nahtlose Endlosschleife) -->
    <section class="carousel-section">
<?php if (!$roster): ?>
        <div class="empty-state carousel-empty">Aktuell keine Bands im Roster hinterlegt.</div>
<?php else: ?>
        <div class="carousel-track">
<?php for ($round = 0; $round < 2; $round++): ?>
<?php foreach ($roster as $artist): ?>
            <a class="carousel-item" href="<?= e(url('artist.php?id=' . $artist['id'])) ?>"<?= $round ? ' aria-hidden="true" tabindex="-1"' : '' ?>>
                <img src="<?= e(media_url($artist['image_url'] ?? '', url('assets/img/havoc-logo.jpeg'))) ?>" alt="<?= e($artist['name']) ?>">
                <div class="carousel-meta">
                    <div class="carousel-band-name"><?= e($artist['name']) ?></div>
                    <div class="carousel-band-genre"><?= e($artist['genre'] ?: 'Alternative') ?></div>
                </div>
            </a>
<?php endforeach; ?>
<?php endfor; ?>
        </div>
<?php endif; ?>
    </section>

    <main>
        <section class="hero">
            <h1>WE WREAK<br><span>HAVOC.</span></h1>
            <p>Artist Booking & Management Agency</p>
        </section>

        <!-- EDITORIAL NEWS: TOP 2 + ARCHIV -->
        <section id="news" class="roster-section news-section">
            <div class="section-heading">Latest Updates //</div>

<?php if (!$latestNews): ?>
            <div class="empty-state">Aktuell keine Ankündigungen vorhanden.</div>
<?php else: ?>
            <div class="news-container">
<?php foreach ($latestNews as $item): ?>
                <article class="news-card">
                    <div class="news-img-box">
<?php if (!empty($item['image_url'])): ?>
                        <img src="<?= e(media_url($item['image_url'])) ?>" alt="">
<?php else: ?>
                        <div class="news-img-placeholder">HAVOC NEWS</div>
<?php endif; ?>
                    </div>
                    <div class="news-content">
                        <div class="news-date"><?= e(format_date_de($item['created_at'] ?? '')) ?></div>
                        <h3 class="news-title"><?= e($item['title']) ?></h3>
                        <div class="news-text"><?= nl2br_e($item['text']) ?></div>
                    </div>
                </article>
<?php endforeach; ?>
            </div>
<?php endif; ?>

<?php if ($archiveNews): ?>
            <div class="archive-section">
                <div class="archive-heading">Previous News</div>
<?php foreach ($archiveNews as $item): ?>
                <details class="archive-item">
                    <summary class="archive-row">
                        <span class="archive-title"><?= e($item['title']) ?></span>
                        <span class="archive-date"><?= e(format_date_de($item['created_at'] ?? '')) ?></span>
                    </summary>
                    <div class="archive-body"><?= nl2br_e($item['text']) ?></div>
                </details>
<?php endforeach; ?>
            </div>
<?php endif; ?>
        </section>

        <!-- KOMPAKTER TOUR-KALENDER: DIE NÄCHSTEN 4 SHOWS -->
        <section id="dates" class="roster-section dates-section">
            <h2 class="dates-heading">Upcoming Live Shows //</h2>

<?php if (!$upcomingDates): ?>
            <div class="empty-state dates-empty">Keine aktuellen Tourdaten vorhanden.</div>
<?php endif; ?>
<?php foreach ($upcomingDates as $show): ?>
            <div class="ticket-row">
                <div class="date-info"><?= e($show['date']) ?></div>
                <div class="show-info">
                    <?= e($show['city']) ?> <span class="separator">//</span> <?= e($show['venue']) ?>
                    <span class="homepage-artist-name"><?= e($show['artist_name']) ?></span>
                </div>
                <a href="<?= e(safe_link($show['link'] ?? '')) ?>" target="_blank" rel="noopener" class="ticket-btn">Tickets</a>
            </div>
<?php endforeach; ?>

            <div class="more-calendar-wrap">
                <a class="more-calendar-btn" href="<?= e(url('kalender.php')) ?>">SHOW ALL DATES //</a>
            </div>
        </section>
    </main>

<?php require BASE_PATH . '/templates/footer.php'; ?>
