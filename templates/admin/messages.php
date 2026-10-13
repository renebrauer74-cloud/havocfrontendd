<?php $allMessages = messages()->latest(); ?>
<div class="dashboard-box">
    <h2>Anfragen</h2>
    <div class="subtitle">Nachrichten aus dem Kontaktformular – neueste zuerst.</div>

    <div class="management-list">
<?php if (!$allMessages): ?>
        <div class="list-empty">Keine Anfragen vorhanden.</div>
<?php endif; ?>
<?php foreach ($allMessages as $message): ?>
        <div class="management-row message-row">
            <div>
                <span class="row-meta"><?= e(date('d.m.Y H:i', strtotime($message['created_at']))) ?></span>
                <span class="row-title"><?= e($message['name']) ?></span>
                <a class="row-meta" href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>
                <p class="message-text"><?= nl2br_e($message['message']) ?></p>
            </div>
            <div class="action-group">
                <?= delete_button('message', $message['id'], 'Anfrage löschen?') ?>
            </div>
        </div>
<?php endforeach; ?>
    </div>
</div>
