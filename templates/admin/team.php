<?php $members = team()->all(); ?>
<div class="dashboard-box">
<?php if ($editing): ?>
    <div class="mode-badge">Bearbeitungs-Modus</div>
<?php endif; ?>
    <h2><?= $editing ? 'Teammitglied bearbeiten' : 'Teammitglied hinzufügen' ?></h2>
    <div class="subtitle">Internes Profil für die Agentur-Teamseite.</div>

    <form method="post" action="<?= e(url('admin/')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="entity" value="team">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $val('id') ?>">

        <div class="form-group"><label for="teamName">Name *</label><input type="text" id="teamName" name="name" required autocomplete="off" value="<?= $val('name') ?>"></div>
        <div class="form-group"><label for="teamPhone">Telefonnummer</label><input type="text" id="teamPhone" name="phone" placeholder="z.B. +49 (0) 123 45678" value="<?= $val('phone') ?>"></div>
        <div class="form-group"><label for="teamEmail">E-Mail-Adresse *</label><input type="email" id="teamEmail" name="email" required autocomplete="off" value="<?= $val('email') ?>"></div>
        <div class="form-group"><label for="teamHobbies">Hobbys (mit Komma trennen)</label><input type="text" id="teamHobbies" name="hobbies" placeholder="z.B. Vinyl, Konzerte, Skaten" value="<?= $val('hobbies') ?>"></div>
        <div class="form-group"><label for="teamBio">Infotext / Biografie</label><textarea id="teamBio" name="bio" rows="6" placeholder="Beschreibe das Teammitglied..."><?= $val('bio') ?></textarea></div>
        <div class="form-group">
            <label>Profilbild (Drag & Drop)</label>
            <?php $dropLabel = 'Profilbild ablegen oder klicken'; $currentImage = $editing['image_url'] ?? null; require __DIR__ . '/dropzone.php'; ?>
        </div>

        <button type="submit" class="submit-btn"><?= $editing ? 'ÄNDERUNGEN SPEICHERN' : 'MITGLIED SPEICHERN' ?></button>
<?php if ($editing): ?>
        <a class="cancel-btn" href="<?= e(url('admin/?section=team')) ?>">ABBRECHEN</a>
<?php endif; ?>
    </form>
</div>

<div class="dashboard-box">
    <h2>Teammitglieder verwalten</h2>
    <div class="management-list">
<?php if (!$members): ?>
        <div class="list-empty">Keine Teammitglieder eingetragen.</div>
<?php endif; ?>
<?php foreach ($members as $member): ?>
        <div class="management-row">
            <div>
                <span class="row-title"><?= e($member['name']) ?></span>
                <span class="row-meta">[<?= e($member['email']) ?>]</span>
<?php if (!empty($member['bio'])): ?>
                <p class="row-excerpt"><?= e(mb_strimwidth($member['bio'], 0, 60, '...')) ?></p>
<?php endif; ?>
            </div>
            <div class="action-group">
                <a class="edit-btn" href="<?= e(url('admin/?section=team&edit=' . $member['id'])) ?>">EDITIEREN</a>
                <?= delete_button('team', $member['id'], 'Teammitglied unwiderruflich löschen?') ?>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</div>
