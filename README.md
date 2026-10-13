# HAVOC AGENCY – Website

Plain PHP (ohne Framework), PHP ≥ 8.2 mit den Extensions `fileinfo`, `mbstring`, `json` und `pdo_mysql`.

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
  Storage/         Table-Interface, PdoTable (MariaDB) und JsonTable (lokale Entwicklung)
  Repository/      Datenzugriff pro Bereich (Artists, Tourdaten, Galerie, News, Team, Shows, Anfragen)
templates/         Header/Footer und Admin-Sektionen
database/          schema.sql (Tabellen für MariaDB/MySQL)
bin/               CLI: Passwort-Hash, Migration, JSON-Import
storage/data/      JSON-Daten für lokale Entwicklung (nicht im Git)
config/            config.php (nicht im Git) + Vorlage
```

## Datenbank

Produktiv liegen die Daten in einer MariaDB 11.8 bei IONOS. Die DB ist **nur vom IONOS-Webspace aus erreichbar**,
deshalb gibt es zwei Treiber, gesteuert über `database.driver` in `config/config.php`:

- `mysql`: MariaDB/MySQL per PDO (Server)
- `json`: JSON-Dateien in `storage/data/` (lokale Entwicklung)

Einrichtung auf dem Server:

1. In `config/config.php` auf dem Server `'driver' => 'mysql'` setzen und die Zugangsdaten im `database`-Block eintragen.
2. Tabellen anlegen: per SSH `php bin/migrate.php` oder `database/schema.sql` in phpMyAdmin importieren.
   Das Schema ist idempotent (`CREATE TABLE IF NOT EXISTS`).
3. Optional bestehende JSON-Daten übernehmen: `php bin/import-json.php` (nur in leere Tabellen, IDs bleiben erhalten).
