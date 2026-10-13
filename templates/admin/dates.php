<?php $allDates = tour_dates()->allWithArtist(artists()); ?>
<div class="dashboard-box">
<?php if ($editing): ?>
    <div class="mode-badge">Bearbeitungs-Modus</div>
<?php endif; ?>
    <h2><?= $editing ? 'Tourtermin bearbeiten' : 'Tourdaten hinzufügen' ?></h2>

<?php if (!$allArtists): ?>
    <div class="subtitle">Lege zuerst einen Künstler an.</div>
<?php else: ?>
    <form method="post" action="<?= e(url('admin/')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="entity" value="date">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $val('id') ?>">

        <div class="form-group">
            <label for="artistSelect">Band auswählen *</label>
            <select id="artistSelect" name="artist_id" required>
                <option value="">-- Band auswählen --</option>
<?php foreach ($allArtists as $artist): ?>
                <option value="<?= $artist['id'] ?>"<?= ($editing['artist_id'] ?? null) === $artist['id'] ? ' selected' : '' ?>><?= e($artist['name']) ?></option>
<?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label for="showDate">Datum *</label><input type="text" id="showDate" name="date" required placeholder="TT.MM.JJJJ" value="<?= $val('date') ?>"></div>
        <div class="form-group"><label for="showCity">Stadt *</label><input type="text" id="showCity" name="city" required value="<?= $val('city') ?>"></div>
        <div class="form-group"><label for="showVenue">Location *</label><input type="text" id="showVenue" name="venue" required value="<?= $val('venue') ?>"></div>
        <div class="form-group"><label for="showLink">Ticket-Link</label><input type="text" id="showLink" name="link" placeholder="https://..." value="<?= $val('link') ?>"></div>

        <button type="submit" class="submit-btn"><?= $editing ? 'ÄNDERUNGEN SPEICHERN' : 'TERMIN SPEICHERN' ?></button>
<?php if ($editing): ?>
        <a class="cancel-btn" href="<?= e(url('admin/?section=dates')) ?>">ABBRECHEN</a>
<?php endif; ?>
    </form>
<?php endif; ?>
</div>

<div class="dashboard-box">
    <h2>Tourdaten verwalten</h2>
    <div class="management-list">
<?php if (!$allDates): ?>
        <div class="list-empty">Noch keine Tourdaten eingetragen.</div>
<?php endif; ?>
<?php foreach ($allDates as $date): ?>
        <div class="management-row">
            <div>
                <span class="row-meta"><?= e($date['date']) ?></span>
                <span class="row-title"><?= e($date['artist_name']) ?></span>
                <span class="row-meta"><?= e($date['city']) ?> // <?= e($date['venue']) ?></span>
            </div>
            <div class="action-group">
                <a class="edit-btn" href="<?= e(url('admin/?section=dates&edit=' . $date['id'])) ?>">EDITIEREN</a>
                <?= delete_button('date', $date['id'], 'Termin löschen?') ?>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</div>
