<x-layouts.site title="HAVOC AGENCY | Contact & Booking" :styles="['kontakt.css']">

    <main>
        <h1 class="page-title">Booking &<span> Contact //</span></h1>

        <div class="contact-split-container">
            <!-- LINKS: FORMULAR FÜR ALLGEMEINE ANFRAGEN -->
            <div class="contact-form-side">
@if ($errors->any())
                <div class="form-status is-error">{{ implode(' ', $errors->all()) }}</div>
@elseif (session('status'))
                <div class="form-status is-success">{{ session('status') }}</div>
@endif

                <form method="post" action="{{ route('contact.store') }}" class="contact-form">
                    @csrf
                    <div class="form-field">
                        <label for="contactName">Name / Agentur</label>
                        <input type="text" id="contactName" name="name" required value="{{ old('name') }}">
                    </div>

                    <div class="form-field">
                        <label for="contactEmail">E-Mail-Adresse</label>
                        <input type="email" id="contactEmail" name="email" required value="{{ old('email') }}">
                    </div>

                    <div class="form-field">
                        <label for="contactMessage">Nachricht</label>
                        <textarea id="contactMessage" name="message" required rows="6">{{ old('message') }}</textarea>
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

</x-layouts.site>
