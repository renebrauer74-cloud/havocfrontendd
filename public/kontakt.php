<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$errors = [];
$values = ['name' => '', 'email' => '', 'message' => ''];

if (is_post()) {
    verify_csrf();

    $values = ['name' => input('name'), 'email' => input('email'), 'message' => input('message')];

    // Honeypot: echte Besucher sehen das Feld nicht und lassen es leer.
    if (input('website') !== '') {
        redirect('kontakt.php');
    }

    if ($values['name'] === '') {
        $errors[] = 'Bitte gib deinen Namen bzw. deine Agentur an.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte gib eine gültige E-Mail-Adresse an.';
    }
    if ($values['message'] === '') {
        $errors[] = 'Bitte schreib uns eine Nachricht.';
    }

    if (!$errors) {
        messages()->create($values);

        $mailTo = (string) config('contact.mail_to', '');
        if ($mailTo !== '') {
            $headers = ['Content-Type' => 'text/plain; charset=UTF-8', 'Reply-To' => $values['email']];
            if ($from = (string) config('contact.mail_from', '')) {
                $headers['From'] = $from;
            }
            $body = "Name: {$values['name']}\nE-Mail: {$values['email']}\n\n{$values['message']}\n";
            @mail($mailTo, '=?UTF-8?B?' . base64_encode('Neue Anfrage über havoc-agency.com') . '?=', $body, $headers);
        }

        flash('contact', 'Danke! Deine Anfrage ist bei uns eingegangen – wir melden uns.');
        redirect('kontakt.php');
    }
}

$status = flash('contact');
if ($errors) {
    $status = ['type' => 'error', 'message' => implode(' ', $errors)];
}

$pageTitle = 'HAVOC AGENCY | Contact & Booking';
$styles = ['kontakt.css'];
require BASE_PATH . '/templates/header.php';
?>

    <main>
        <h1 class="page-title">Booking &<span> Contact //</span></h1>

        <div class="contact-split-container">
            <!-- LINKS: FORMULAR FÜR ALLGEMEINE ANFRAGEN -->
            <div class="contact-form-side">
<?php if ($status): ?>
                <div class="form-status is-<?= e($status['type']) ?>"><?= e($status['message']) ?></div>
<?php endif; ?>

                <form method="post" action="<?= e(url('kontakt.php')) ?>" class="contact-form">
                    <?= csrf_field() ?>
                    <div class="form-field">
                        <label for="contactName">Name / Agentur</label>
                        <input type="text" id="contactName" name="name" required value="<?= e($values['name']) ?>">
                    </div>

                    <div class="form-field">
                        <label for="contactEmail">E-Mail-Adresse</label>
                        <input type="email" id="contactEmail" name="email" required value="<?= e($values['email']) ?>">
                    </div>

                    <div class="form-field">
                        <label for="contactMessage">Nachricht</label>
                        <textarea id="contactMessage" name="message" required rows="6"><?= e($values['message']) ?></textarea>
                    </div>

                    <div class="form-field hp-field" aria-hidden="true">
                        <label for="contactWebsite">Website</label>
                        <input type="text" id="contactWebsite" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <button type="submit" class="ticket-btn">Anfrage senden</button>
                </form>
            </div>

            <!-- RECHTS: STRUKTURIERTE KONTAKTLISTE & INITIATIVBEWERBUNGEN -->
            <div class="contact-info-side">
                <div class="contact-info-block">
                    <h3>Initiativbewerbungen //</h3>
                    <p>Du willst als <strong>Booker</strong> oder <strong>Artist Manager</strong> das Roster pushen? Sende uns deine aussagekräftige Bewerbung direkt über unseren Bewerber-Service:</p>
                    <a href="mailto:apply@havoc-agency.com" class="contact-mail-link">apply@havoc-agency.com</a>
                </div>

                <div class="contact-info-block">
                    <h3>Band Bewerbung //</h3>
                    <p>Deine Band ist hungrig nach Live-Shows und sucht professionelles Management? Schicke uns dein Pressekit, Spotify-Links und deine Tour-Historie exakt hierhin:</p>
                    <a href="mailto:booking@havoc-agency.com" class="contact-mail-link">booking@havoc-agency.com</a>
                </div>

                <div class="contact-info-block">
                    <h3>Allgemeine Infos //</h3>
                    <p>Für alle allgemeinen Fragen, Presse-Akkreditierungen oder Kooperationswünsche schreibe uns jederzeit an diese Adresse:</p>
                    <a href="mailto:info@havoc-agency.com" class="contact-mail-link">info@havoc-agency.com</a>
                </div>
            </div>
        </div>
    </main>

<?php require BASE_PATH . '/templates/footer.php'; ?>
