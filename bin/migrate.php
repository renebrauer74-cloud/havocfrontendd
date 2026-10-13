<?php
// Legt die Tabellen aus database/schema.sql an (idempotent):  php bin/migrate.php
// Ohne SSH-Zugang: database/schema.sql stattdessen in phpMyAdmin importieren.
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$pdo = db()->pdo();
$sql = (string) file_get_contents(BASE_PATH . '/database/schema.sql');

// Kommentare entfernen, dann an Semikolons am Zeilenende trennen.
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
foreach (preg_split('/;\s*$/m', $sql) as $statement) {
    if (trim($statement) !== '') {
        $pdo->exec($statement);
    }
}

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'Schema aktuell. Tabellen: ', implode(', ', $tables), PHP_EOL;
