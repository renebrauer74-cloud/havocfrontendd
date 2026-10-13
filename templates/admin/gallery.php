<?php
use Havoc\Repository\GalleryRepository;

$galleryArtistId = (int) ($_GET['artist'] ?? 0);
$photos = $galleryArtistId ? gallery()->forArtist($galleryArtistId) : [];
?>
<div class="dashboard-box">
    <h2>Bildergalerie verwalten (Max. <?= GalleryRepository::MAX_PER_ARTIST ?>)</h2>

    <form method="get" action="<?= e(url('admin/')) ?>" class="form-group">
        <input type="hidden" name="section" value="gallery">
        <label for="galleryArtistSelect">Band auswählen</label>
        <select id="galleryArtistSelect" name="artist" data-autosubmit>
            <option value="">-- Band auswählen --</option>
<?php foreach ($allArtists as $artist): ?>
            <option value="<?= $artist['id'] ?>"<?= $artist['id'] === $galleryArtistId ? ' selected' : '' ?>><?= e($artist['name']) ?></option>
<?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="edit-btn">AUSWÄHLEN</button></noscript>
    </form>

<?php if ($galleryArtistId): ?>
    <form method="post" action="<?= e(url('admin/')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="entity" value="gallery">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="artist_id" value="<?= $galleryArtistId ?>">
        <div class="form-group">
            <label>Neues Foto (Drag & Drop)</label>
            <?php $dropLabel = 'Galeriebild hier ablegen oder klicken'; require __DIR__ . '/dropzone.php'; ?>
        </div>
        <button type="submit" class="submit-btn"<?= count($photos) >= GalleryRepository::MAX_PER_ARTIST ? ' disabled' : '' ?>>BILD HOCHLADEN</button>
    </form>
<?php endif; ?>
</div>

<div class="dashboard-box">
    <h2>Aktuelle Galeriebilder</h2>
    <div class="gallery-grid">
<?php if (!$galleryArtistId): ?>
        <div class="list-empty gallery-hint">Bitte wähle zuerst eine Band aus.</div>
<?php elseif (!$photos): ?>
        <div class="list-empty gallery-hint">Noch keine Galeriebilder für diese Band.</div>
<?php endif; ?>
<?php foreach ($photos as $photo): ?>
        <div class="gallery-manage-card">
            <img src="<?= e(media_url($photo['image_url'])) ?>" alt="">
            <?= delete_button('gallery', $photo['id'], 'Bild entfernen?', 'ENTFERNEN', ['artist_id' => $galleryArtistId]) ?>
        </div>
<?php endforeach; ?>
    </div>
</div>
