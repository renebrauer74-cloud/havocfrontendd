<?php $allNews = news()->latest(); ?>
<div class="dashboard-box">
<?php if ($editing): ?>
    <div class="mode-badge">Bearbeitungs-Modus</div>
<?php endif; ?>
    <h2><?= $editing ? 'News bearbeiten' : 'News verfassen' ?></h2>

    <form method="post" action="<?= e(url('admin/')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="entity" value="news">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $val('id') ?>">

        <div class="form-group"><label for="newsTitle">Titel *</label><input type="text" id="newsTitle" name="title" required autocomplete="off" value="<?= $val('title') ?>"></div>
        <div class="form-group"><label for="newsText">Inhalt *</label><textarea id="newsText" name="text" rows="6" required><?= $val('text') ?></textarea></div>
        <div class="form-group">
            <label>News-Bild (Optional)</label>
            <?php $dropLabel = 'News-Bild ablegen oder klicken'; $currentImage = $editing['image_url'] ?? null; require __DIR__ . '/dropzone.php'; ?>
        </div>

        <button type="submit" class="submit-btn"><?= $editing ? 'ÄNDERUNGEN SPEICHERN' : 'NEWS VERÖFFENTLICHEN' ?></button>
<?php if ($editing): ?>
        <a class="cancel-btn" href="<?= e(url('admin/?section=news')) ?>">ABBRECHEN</a>
<?php endif; ?>
    </form>
</div>

<div class="dashboard-box">
    <h2>News verwalten</h2>
    <div class="management-list">
<?php if (!$allNews): ?>
        <div class="list-empty">Noch keine News veröffentlicht.</div>
<?php endif; ?>
<?php foreach ($allNews as $item): ?>
        <div class="management-row">
            <div>
                <span class="row-meta"><?= e(format_date_de($item['created_at'] ?? '')) ?></span>
                <span class="row-title"><?= e($item['title']) ?></span>
            </div>
            <div class="action-group">
                <a class="edit-btn" href="<?= e(url('admin/?section=news&edit=' . $item['id'])) ?>">EDITIEREN</a>
                <?= delete_button('news', $item['id'], 'News löschen?') ?>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</div>
