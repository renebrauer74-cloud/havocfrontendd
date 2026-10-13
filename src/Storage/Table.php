<?php
declare(strict_types=1);

namespace Havoc\Storage;

/**
 * Minimale Tabellen-Schnittstelle. Die Repositories kennen nur dieses Interface –
 * für die spätere DB-Anbindung reicht eine PdoTable-Implementierung.
 */
interface Table
{
    /** @return list<array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array;

    /** @return list<array<string, mixed>> */
    public function where(string $field, mixed $value): array;

    /** @param array<string, mixed> $row  @return int neue ID */
    public function insert(array $row): int;

    /** @param array<string, mixed> $row */
    public function update(int $id, array $row): bool;

    public function delete(int $id): bool;

    /** @return int Anzahl gelöschter Zeilen */
    public function deleteWhere(string $field, mixed $value): int;
}
