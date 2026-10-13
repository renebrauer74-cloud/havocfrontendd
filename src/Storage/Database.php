<?php
declare(strict_types=1);

namespace Havoc\Storage;

use PDO;
use RuntimeException;

/**
 * Liefert die Tabellen für die Repositories – je nach config('database.driver')
 * aus MariaDB/MySQL ('mysql') oder als JSON-Dateien in storage/data/ ('json', lokale Entwicklung).
 */
final class Database
{
    /** @var array<string, Table> */
    private array $tables = [];

    private ?PDO $pdo = null;

    /** @param array<string, mixed> $config Abschnitt 'database' aus der Config */
    public function __construct(private readonly array $config, private readonly string $jsonDirectory)
    {
    }

    public function table(string $name): Table
    {
        return $this->tables[$name] ??= $this->usesPdo()
            ? new PdoTable($this->pdo(), $name)
            : new JsonTable($this->jsonDirectory() . '/' . $name . '.json');
    }

    public function usesPdo(): bool
    {
        return ($this->config['driver'] ?? 'json') === 'mysql';
    }

    public function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }
        if (!$this->usesPdo()) {
            throw new RuntimeException("database.driver ist nicht 'mysql'.");
        }
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('PHP-Erweiterung pdo_mysql ist auf diesem Server nicht aktiv.');
        }
        // Ab PHP 8.5 ist PDO::MYSQL_ATTR_FOUND_ROWS deprecated, Pdo\Mysql gibt es erst ab 8.4.
        $foundRows = defined('Pdo\Mysql::ATTR_FOUND_ROWS')
            ? constant('Pdo\Mysql::ATTR_FOUND_ROWS')
            : constant('PDO::MYSQL_ATTR_FOUND_ROWS');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->config['host'] ?? 'localhost',
            (int) ($this->config['port'] ?? 3306),
            $this->config['name'] ?? ''
        );

        return $this->pdo = new PDO($dsn, $this->config['user'] ?? '', $this->config['password'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Echte Prepared Statements: Integer-Spalten kommen als int zurück (strikte Vergleiche bleiben gültig).
            PDO::ATTR_EMULATE_PREPARES   => false,
            $foundRows                   => true,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
    }

    private function jsonDirectory(): string
    {
        if (!is_dir($this->jsonDirectory) && !mkdir($this->jsonDirectory, 0775, true) && !is_dir($this->jsonDirectory)) {
            throw new RuntimeException("Datenverzeichnis kann nicht angelegt werden: {$this->jsonDirectory}");
        }
        return $this->jsonDirectory;
    }
}
