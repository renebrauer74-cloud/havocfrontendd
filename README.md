# HAVOC AGENCY – Website

Laravel 13 (PHP ≥ 8.3) mit Blade-Templates. Kein Frontend-Build nötig: CSS und JS liegen fertig unter `public/assets/`.

## Lokal starten

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed        # Demo-Inhalte + Admin "admin" / "havoc-dev"
php artisan serve
```

Dann <http://localhost:8000> öffnen. Der Admin-Bereich liegt unter `/admin`.
Lokal läuft die Datenbank als SQLite-Datei (`database/database.sqlite`).

Tests: `php artisan test --compact` · Code-Stil: `vendor/bin/pint`

## Struktur

```
app/
  Http/Controllers/        öffentliche Seiten (Home, Artist, Calendar, AgencyShow, Team, Contact)
  Http/Controllers/Admin/  Backend: CRUD pro Bereich (Künstler, Tourdaten, Galerie, News, Team, Shows, Anfragen)
  Http/Requests/           Validierung (Formular-Requests)
  Models/                  Eloquent-Models; Concerns/HasUploadedImage = Bild-Upload, Collections/ShowDateCollection = Freitext-Datum sortieren
  Console/Commands/        havoc:admin, havoc:import-json, band-manager:admin
  helpers.php              media_url(), safe_link(), placeholder_image()
config/havoc.php           Kontakt-Mail und Upload-Limit
database/migrations/       Tabellen (überspringen bereits vorhandene Tabellen aus dem alten schema.sql)
resources/views/           Blade: pages/, shows/, admin/, components/ (Layouts, Dropzone, Lösch-Button)
routes/web.php             alle Routen inkl. 301-Weiterleitungen der alten *.php-URLs
public/                    Webroot: assets/ (CSS, JS, Logo), uploads/ (hochgeladene Bilder, nicht im Git)
lang/de/validation.php     deutsche Fehlermeldungen
```

## Admin-Zugang

Admins liegen in der Tabelle `users` und werden per Artisan verwaltet:

```bash
php artisan havoc:admin rene                                  # fragt das Passwort ab
php artisan havoc:admin admin --password-hash='$2y$12$...'     # Hash aus der alten config/config.php übernehmen
```

## Band-Manager (`/band-manager`)

Interner Bereich für Agentur und Bands mit **eigenem Login** (Tabelle `bm_users`, Guard `band-manager`) –
unabhängig vom Website-Admin. Funktionen: Ampel-Kalender, Gigs mit Day Sheet (PDF), Band & Team mit Bandprofil,
Bildergalerie, Verträge & Rider, Nachrichten Agentur ↔ Band, CSV-Export und Pressekit (ZIP).

```bash
php artisan band-manager:admin rene@havoc-agency.com --name="Rene"   # Agentur-Admin anlegen, fragt das Passwort ab
php artisan band-manager:admin rene@havoc-agency.com --password="…"  # ohne Abfrage, z.B. auf Laravel Cloud
php artisan db:seed --class=BandManagerSeeder                       # lokal: admin@havoc.test / band@havoc.test (havoc-dev)
```

Bands und weitere Logins legt der Admin danach unter *Administration → Bands / Benutzer* an.

```
app/Http/Controllers/BandManager/   Controller (Admin/ = Dashboard, Benutzer, Bands; Api/ = Kalender-JSON)
app/Models/BandManager/             Models (Tabellen mit Präfix bm_)
app/Policies/BandManager/           Band-, Gig- und Dokument-Zugriff
app/Services/BandManager/           Navigation, Impersonation, Markdown, PDF, Day Sheet, Pressekit, Bilder …
config/band-manager.php             Rollen & Rechte, Status, Kategorien, Upload-Limits, Pressekit-Kontakt
routes/band-manager.php             Routen (Präfix /band-manager, Namen band-manager.*)
resources/views/band-manager/       Blade-Views, Layout unter components/band-manager/
public/assets/band-manager/         CSS, JS (Kalender, Hell/Dunkel, Formatierung), Logo
storage/app/private/band-manager/   Uploads (team/, gallery/, documents/) – nicht öffentlich, Auslieferung nur mit Rechteprüfung
```

Rechte stehen pro Rolle in `config/band-manager.php` und werden als Gate geprüft (`@can('gigs.view')`).
Admins (`*`) dürfen alles, Bands nur Daten ihrer eigenen Bands.

Voraussetzungen auf dem Server: PHP-Erweiterungen `gd` (Foto-Verkleinerung), `zip` (Pressekit), `fileinfo`, `mbstring`.
`storage/` muss beschreibbar sein. Upload-Limits (bis 50 MB pro Datei) passen zu `public/.user.ini`.

## Produktion (IONOS)

Die MariaDB 11.8 ist **nur vom IONOS-Webspace aus erreichbar**. Alle Befehle unten also per SSH auf dem Server ausführen.

### Umstieg von der alten Plain-PHP-Version (einmalig)

1. **Alte Dateien auf dem Server entfernen:** `src/`, `templates/`, `bin/`, `public/admin/` und die alten Seiten
   `public/*.php` (außer dem neuen `public/index.php`). Sonst liefert der Webserver sie weiter direkt aus.
   `public/uploads/` **behalten** – die gespeicherten Bildpfade bleiben gültig.
2. Die Werte aus der alten `config/config.php` notieren (DB-Zugang, Admin-Hash, `contact.mail_to`)
   und die Datei danach löschen – Laravel würde sonst jede Datei in `config/` als Konfiguration laden.
3. Code hochladen und Abhängigkeiten installieren: `composer install --no-dev --optimize-autoloader`
   (oder `vendor/` lokal so bauen und mit hochladen).
4. `.env` aus `.env.example` anlegen und anpassen:
   `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `DB_CONNECTION=mariadb` + Zugangsdaten,
   `MAIL_MAILER=sendmail` (oder SMTP), `CONTACT_MAIL_TO=…`. Danach `php artisan key:generate`.
5. `php artisan migrate --force` – vorhandene Tabellen aus dem alten `schema.sql` bleiben unverändert,
   neu angelegt werden nur `users`, `sessions`, `cache`, `jobs` und `migrations`.
6. Lagen die Daten bisher noch als JSON auf dem Server: die Dateien nach `storage/data/` legen und
   `php artisan havoc:import-json` ausführen (nur in leere Tabellen, IDs bleiben erhalten).
7. Admin anlegen: `php artisan havoc:admin admin --password-hash='<password_hash aus der alten Config>'`.
   Für den Band-Manager zusätzlich: `php artisan band-manager:admin <e-mail>`.
8. Caches bauen: `php artisan config:cache && php artisan route:cache && php artisan view:cache`.

### Bei jedem Deployment

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

Der Webroot der Domain muss auf `public/` zeigen. `storage/` und `bootstrap/cache/` müssen beschreibbar sein.
Die Upload-Limits für PHP stehen in `public/.user.ini` und passen zu `UPLOAD_MAX_KILOBYTES` in der `.env`.

Die alten URLs (`/roster.php`, `/artist.php?id=…` usw.) leiten dauerhaft (301) auf die neuen um.
