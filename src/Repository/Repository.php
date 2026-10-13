<?php
declare(strict_types=1);

namespace Havoc\Repository;

use Havoc\Storage\Table;

/** Basis für alle Repositories: CRUD mit Feld-Whitelist. */
abstract class Repository
{
    /** Felder, die gespeichert werden dürfen. */
    protected const FIELDS = [];

    public function __construct(protected readonly Table $table)
    {
    }

    public function all(): array
    {
        return $this->table->all();
    }

    public function find(int $id): ?array
    {
        return $this->table->find($id);
    }

    public function create(array $data): int
    {
        return $this->table->insert($this->only($data));
    }

    public function update(int $id, array $data): bool
    {
        return $this->table->update($id, $this->only($data));
    }

    public function delete(int $id): bool
    {
        return $this->table->delete($id);
    }

    protected function only(array $data): array
    {
        return array_intersect_key($data, array_flip(static::FIELDS));
    }
}
