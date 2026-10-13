<div class="dashboard-box">
<?php if ($editing): ?>
    <div class="mode-badge">Bearbeitungs-Modus</div>
<?php endif; ?>
    <h2><?= $editing ? 'Künstler bearbeiten' : 'Künstler hinzufügen' ?></h2>
    <div class="subtitle">Erstelle ein neues zweisprachiges Bandprofil.</div>

    <form method="post" action="<?= e(url('admin/')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="entity" value="artist">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $val('id') ?>">

        <div class="form-group"><label for="artistName">Bandname *</label><input type="text" id="artistName" name="name" required autocomplete="off" value="<?= $val('name') ?>"></div>
        <div class="form-group"><label for="artistGenre">Genre</label><input type="text" id="artistGenre" name="genre" placeholder="z.B. Post-Hardcore" value="<?= $val('genre') ?>"></div>
        <div class="form-group"><label for="artistBio">Biografie (DE)</label><textarea id="artistBio" name="bio" rows="4"><?= $val('bio') ?></textarea></div>
        <div class="form-group"><label for="artistBioEn">Biography (EN)</label><textarea id="artistBioEn" name="bio_en" rows="4"><?= $val('bio_en') ?></textarea></div>
        <div class="form-group"><label for="artistSpotify">Spotify URL</label><input type="text" id="artistSpotify" name="spotify_url" value="<?= $val('spotify_url') ?>"></div>
        <div class="form-group"><label for="artistYoutube">YouTube URL</label><input type="text" id="artistYoutube" name="youtube_url" value="<?= $val('youtube_url') ?>"></div>
        <div class="form-group"><label for="artistWebsite">Offizielle Webseite</label><input type="text" id="artistWebsite" name="website_url" value="<?= $val('website_url') ?>"></div>
        <div class="form-group"><label for="artistInstagram">Instagram URL</label><input type="text" id="artistInstagram" name="instagram_url" value="<?= $val('instagram_url') ?>"></div>
        <div class="form-group"><label for="artistTiktok">TikTok URL</label><input type="text" id="artistTiktok" name="tiktok_url" value="<?= $val('tiktok_url') ?>"></div>
        <div class="form-group">
            <label>Bild (Drag & Drop)</label>
            <?php $dropLabel = 'Bild hier ablegen oder klicken'; $currentImage = $editing['image_url'] ?? null; require __DIR__ . '/dropzone.php'; ?>
        </div>

        <button type="submit" class="submit-btn"><?= $editing ? 'ÄNDERUNGEN SPEICHERN' : 'KÜNSTLER SPEICHERN' ?></button>
<?php if ($editing): ?>
        <a class="cancel-btn" href="<?= e(url('admin/?section=artists')) ?>">ABBRECHEN</a>
<?php endif; ?>
    </form>
</div>

<div class="dashboard-box">
    <h2>Künstler verwalten</h2>
    <div class="management-list">
<?php if (!$allArtists): ?>
        <div class="list-empty">Noch keine Künstler eingetragen.</div>
<?php endif; ?>
<?php foreach ($allArtists as $artist): ?>
        <div class="management-row">
            <span class="row-title"><?= e($artist['name']) ?></span>
            <div class="action-group">
                <a class="edit-btn" href="<?= e(url('admin/?section=artists&edit=' . $artist['id'])) ?>">EDITIEREN</a>
                <?= delete_button('artist', $artist['id'], 'Künstler inkl. Tourdaten und Galerie unwiderruflich löschen?') ?>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</div>
