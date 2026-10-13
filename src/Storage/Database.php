<?php
declare(strict_types=1);

namespace Havoc\Storage;

use RuntimeException;

/**
 * Liefert die Tabellen für die Repositories.
 * TODO DB-Anbindung: hier statt JsonTable eine PdoTable (gleiches Table-Interface) zurückgeben.
 */
final class Database
{
    /** @var array<string, Table> */
    private array $tables = [];

    public function __construct(private readonly string $directory)
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException("Datenverzeichnis kann nicht angelegt werden: {$this->directory}");
        }
    }

    public function table(string $name): Table
    {
        return $this->tables[$name] ??= new JsonTable($this->directory . '/' . $name . '.json');
    }
}
