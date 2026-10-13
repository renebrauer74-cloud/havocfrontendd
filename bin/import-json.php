<?php
// Übernimmt die JSON-Daten aus storage/data/ einmalig in die Datenbank:  php bin/import-json.php
// IDs bleiben erhalten (Verknüpfungen artist_id). Nur in leere Tabellen – sonst Abbruch.
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// Reihenfolge wegen Fremdschlüsseln: artists vor tour_dates und gallery.
const TABLES = ['artists', 'tour_dates', 'gallery', 'news', 'team', 'agency_shows', 'messages'];

$pdo = db()->pdo();

foreach (TABLES as $table) {
    if ((int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() > 0) {
        fwrite(STDERR, "Abbruch: Tabelle '{$table}' ist nicht leer.\n");
        exit(1);
    }
}

$pdo->beginTransaction();
try {
    foreach (TABLES as $table) {
        $file = BASE_PATH . "/storage/data/{$table}.json";
        if (!is_file($file)) {
            echo "{$table}: keine JSON-Datei, übersprungen\n";
            continue;
        }

        $rows = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR)['rows'] ?? [];
        $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($rows as $row) {
            $row = array_intersect_key($row, array_flip($columns));
            foreach (['created_at', 'updated_at'] as $timestamp) {
                if (!empty($row[$timestamp])) {
                    $row[$timestamp] = date('Y-m-d H:i:s', strtotime($row[$timestamp]));
                }
            }
            $names = implode(', ', array_map(static fn (string $c): string => "`{$c}`", array_keys($row)));
            $placeholders = implode(', ', array_fill(0, count($row), '?'));
            $pdo->prepare("INSERT INTO `{$table}` ({$names}) VALUES ({$placeholders})")->execute(array_values($row));
        }
        echo "{$table}: ", count($rows), " Datensätze übernommen\n";
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Fehler, nichts übernommen: ' . $e->getMessage() . "\n");
    exit(1);
}
