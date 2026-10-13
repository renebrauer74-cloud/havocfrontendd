# HAVOC AGENCY – Website

Plain PHP (ohne Framework), PHP ≥ 8.2 mit den Extensions `fileinfo`, `mbstring` und `json`.

## Lokal starten

```bash
cp config/config.example.php config/config.php
php -S localhost:8000 -t public
```

Dann <http://localhost:8000> öffnen. Der Admin-Bereich liegt unter `/admin/`. Die Zugangsdaten stehen in `config/config.php`.
Einen neuen Passwort-Hash erzeugst du mit `php bin/hash-password.php "neues-passwort"`.

Das Logo gehört nach `public/assets/img/havoc-logo.jpeg`.

## Struktur

```
public/            Webroot (nur dieser Ordner ist von außen erreichbar)
  *.php            öffentliche Seiten
  admin/           Login + Dashboard
  assets/          CSS (base.css + eine Datei pro Seite), JS, Bilder
  uploads/         hochgeladene Bilder (nicht im Git)
src/
  bootstrap.php    Autoload, Config, Session
  helpers.php      Escaping, URLs, CSRF, Flash, Repository-Zugriff
  Auth.php         Session-Login für den Admin
  Upload.php       Bild-Uploads (MIME-Prüfung, zufällige Dateinamen)
  Storage/         Table-Interface + JsonTable (Übergangsspeicher)
  Repository/      Datenzugriff pro Bereich (Artists, Tourdaten, Galerie, News, Team, Shows, Anfragen)
templates/         Header/Footer und Admin-Sektionen
storage/data/      JSON-Daten (nicht im Git)
config/            config.php (nicht im Git) + Vorlage
```

## TODO: Datenbank-Anbindung

Aktuell speichern die Repositories ihre Daten als JSON in `storage/data/`. Für die DB:

1. Eine `PdoTable` schreiben, die `Havoc\Storage\Table` implementiert.
2. In `Havoc\Storage\Database::table()` statt `JsonTable` eine `PdoTable` zurückgeben, mit Zugangsdaten aus der Config.
3. Tabellen anlegen: `artists`, `tour_dates`, `gallery`, `news`, `team`, `agency_shows`, `messages`. Die Felder stehen jeweils in `FIELDS` der Repositories, dazu kommen `id`, `created_at` und `updated_at`.
4. Bestehende Inhalte aus dem alten Render-Backend übernehmen. Dort sind Bios und Infotexte Base64-kodiert und Bilder teils als Data-URIs gespeichert.
