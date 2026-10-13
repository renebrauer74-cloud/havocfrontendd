<?php $allShows = agency_shows()->all(); ?>
<div class="dashboard-box">
<?php if ($editing): ?>
    <div class="mode-badge">Bearbeitungs-Modus</div>
<?php endif; ?>
    <h2><?= $editing ? 'Agency Show bearbeiten //' : 'Agency Show hinzufügen //' ?></h2>
    <div class="subtitle">Exklusives Event oder Festival für die Agentur-Showseite.</div>

    <form method="post" action="<?= e(url('admin/')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="entity" value="show">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $val('id') ?>">

        <div class="form-group"><label for="showTitle">Name der Show / Festival *</label><input type="text" id="showTitle" name="title" required autocomplete="off" value="<?= $val('title') ?>"></div>
        <div class="form-group"><label for="agencyShowDate">Datum *</label><input type="text" id="agencyShowDate" name="date" required placeholder="TT.MM.JJJJ oder z.B. 12.-14. JUNI 2026" value="<?= $val('date') ?>"></div>
        <div class="form-group"><label for="agencyShowCity">Stadt *</label><input type="text" id="agencyShowCity" name="city" required autocomplete="off" value="<?= $val('city') ?>"></div>
        <div class="form-group"><label for="agencyShowVenue">Location / Venue *</label><input type="text" id="agencyShowVenue" name="venue" required autocomplete="off" value="<?= $val('venue') ?>"></div>
        <div class="form-group"><label for="agencyShowTicketUrl">Ticket-Link</label><input type="text" id="agencyShowTicketUrl" name="ticket_url" placeholder="https://..." value="<?= $val('ticket_url') ?>"></div>
        <div class="form-group"><label for="agencyShowInfoText">Ausführlicher Infotext (Detailseite)</label><textarea id="agencyShowInfoText" name="info_text" rows="6" placeholder="Bands, Zeitplan, zusätzliche Infos..."><?= $val('info_text') ?></textarea></div>
        <div class="form-group">
            <label>Festival-Plakat / Show-Bild (Drag & Drop)</label>
            <?php $dropLabel = 'Show-Bild ablegen oder klicken'; $currentImage = $editing['image_url'] ?? null; require __DIR__ . '/dropzone.php'; ?>
        </div>

        <button type="submit" class="submit-btn"><?= $editing ? 'ÄNDERUNGEN SPEICHERN' : 'SHOW VERÖFFENTLICHEN' ?></button>
<?php if ($editing): ?>
        <a class="cancel-btn" href="<?= e(url('admin/?section=shows')) ?>">ABBRECHEN</a>
<?php endif; ?>
    </form>
</div>

<div class="dashboard-box">
    <h2>Agency Shows verwalten</h2>
    <div class="management-list">
<?php if (!$allShows): ?>
        <div class="list-empty">Keine exklusiven Shows eingetragen.</div>
<?php endif; ?>
<?php foreach ($allShows as $show): ?>
        <div class="management-row">
            <div>
                <span class="row-title"><?= e($show['title']) ?></span>
                <span class="row-meta">[<?= e($show['date']) ?> — <?= e($show['city']) ?>]</span>
            </div>
            <div class="action-group">
                <a class="edit-btn" href="<?= e(url('admin/?section=shows&edit=' . $show['id'])) ?>">EDITIEREN</a>
                <?= delete_button('show', $show['id'], 'Diese Show unwiderruflich löschen?') ?>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</div>
