<?php
declare(strict_types=1);

namespace Havoc\Storage;

use InvalidArgumentException;
use PDO;

/** Tabelle in MariaDB/MySQL – gleiches Verhalten wie JsonTable. */
final class PdoTable implements Table
{
    private readonly string $quotedName;

    public function __construct(private readonly PDO $pdo, string $name)
    {
        $this->quotedName = self::quote($name);
    }

    public function all(): array
    {
        return $this->pdo->query("SELECT * FROM {$this->quotedName} ORDER BY `id`")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM {$this->quotedName} WHERE `id` = ?");
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function where(string $field, mixed $value): array
    {
        $statement = $this->pdo->prepare("SELECT * FROM {$this->quotedName} WHERE " . self::quote($field) . ' = ? ORDER BY `id`');
        $statement->execute([$value]);
        return $statement->fetchAll();
    }

    public function insert(array $row): int
    {
        $row += ['created_at' => date('Y-m-d H:i:s')];
        $columns = implode(', ', array_map(self::quote(...), array_keys($row)));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));

        $statement = $this->pdo->prepare("INSERT INTO {$this->quotedName} ({$columns}) VALUES ({$placeholders})");
        $statement->execute(array_values($row));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $row): bool
    {
        unset($row['id']);
        $row['updated_at'] = date('Y-m-d H:i:s');
        $assignments = implode(', ', array_map(static fn (string $c): string => self::quote($c) . ' = ?', array_keys($row)));

        $statement = $this->pdo->prepare("UPDATE {$this->quotedName} SET {$assignments} WHERE `id` = ?");
        $statement->execute([...array_values($row), $id]);
        // Verbindung nutzt MYSQL_ATTR_FOUND_ROWS: auch unveränderte Treffer zählen.
        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare("DELETE FROM {$this->quotedName} WHERE `id` = ?");
        $statement->execute([$id]);
        return $statement->rowCount() > 0;
    }

    public function deleteWhere(string $field, mixed $value): int
    {
        $statement = $this->pdo->prepare("DELETE FROM {$this->quotedName} WHERE " . self::quote($field) . ' = ?');
        $statement->execute([$value]);
        return $statement->rowCount();
    }

    /** Bezeichner kommen aus dem Code, werden aber trotzdem streng geprüft. */
    private static function quote(string $identifier): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $identifier)) {
            throw new InvalidArgumentException("Ungültiger Bezeichner: {$identifier}");
        }
        return "`{$identifier}`";
    }
}
