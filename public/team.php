<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$members = team()->all();

$pageTitle = 'HAVOC AGENCY | TEAM';
$layout = 'editorial';
$styles = ['team.css'];
require BASE_PATH . '/templates/header.php';
?>

    <div class="content-container">
        <main>
            <h1 class="page-title">OUR TEAM //</h1>

            <div class="team-grid">
<?php if (!$members): ?>
                <p class="list-empty">Keine Teammitglieder eingetragen.</p>
<?php endif; ?>
<?php foreach ($members as $member): ?>
                <div class="team-card">
                    <div class="profile-wrapper">
                        <img src="<?= e(media_url($member['image_url'] ?? '', placeholder_image(100, 100, 'HAVOC', 12))) ?>" alt="<?= e($member['name']) ?>" class="profile-img">
                    </div>
                    <h2 class="member-name"><?= e($member['name']) ?></h2>

                    <div class="metadata-group">
                        <div class="metadata-row"><span class="metadata-label">FON //</span><span class="metadata-value"><?= e($member['phone'] ?: '—') ?></span></div>
                        <div class="metadata-row"><span class="metadata-label">MAIL //</span><span class="metadata-value"><a href="mailto:<?= e($member['email']) ?>"><?= e($member['email']) ?></a></span></div>
                        <div class="metadata-row"><span class="metadata-label">HOBBY //</span><span class="metadata-value"><?= e($member['hobbies'] ?: '—') ?></span></div>
                    </div>

                    <p class="member-bio"><?= nl2br_e($member['bio'] ?? '') ?></p>
                </div>
<?php endforeach; ?>
            </div>
        </main>
    </div>

<?php require BASE_PATH . '/templates/footer.php'; ?>
