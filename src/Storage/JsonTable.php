<?php
declare(strict_types=1);

namespace Havoc\Storage;

use RuntimeException;

/**
 * Übergangs-Speicher bis zur DB-Anbindung: eine JSON-Datei pro Tabelle,
 * Schreibzugriffe exklusiv per flock gesperrt.
 */
final class JsonTable implements Table
{
    public function __construct(private readonly string $file)
    {
    }

    public function all(): array
    {
        return $this->read()['rows'];
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    public function where(string $field, mixed $value): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (array $row): bool => ($row[$field] ?? null) === $value
        ));
    }

    public function insert(array $row): int
    {
        return $this->write(static function (array &$data) use ($row): int {
            $id = $data['next_id']++;
            $data['rows'][] = ['id' => $id] + $row + ['created_at' => date('c')];
            return $id;
        });
    }

    public function update(int $id, array $row): bool
    {
        return $this->write(static function (array &$data) use ($id, $row): bool {
            foreach ($data['rows'] as &$existing) {
                if ($existing['id'] === $id) {
                    $existing = array_merge($existing, $row, ['id' => $id, 'updated_at' => date('c')]);
                    return true;
                }
            }
            return false;
        });
    }

    public function delete(int $id): bool
    {
        return $this->write(static function (array &$data) use ($id): bool {
            $before = count($data['rows']);
            $data['rows'] = array_values(array_filter($data['rows'], static fn (array $r): bool => $r['id'] !== $id));
            return count($data['rows']) < $before;
        });
    }

    public function deleteWhere(string $field, mixed $value): int
    {
        return $this->write(static function (array &$data) use ($field, $value): int {
            $before = count($data['rows']);
            $data['rows'] = array_values(array_filter(
                $data['rows'],
                static fn (array $r): bool => ($r[$field] ?? null) !== $value
            ));
            return $before - count($data['rows']);
        });
    }

    /** @return array{next_id: int, rows: list<array<string, mixed>>} */
    private function read(): array
    {
        if (!is_file($this->file)) {
            return self::emptyData();
        }
        $handle = fopen($this->file, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Datei nicht lesbar: {$this->file}");
        }
        flock($handle, LOCK_SH);
        $contents = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return self::decode((string) $contents);
    }

    private function write(callable $mutation): mixed
    {
        $handle = fopen($this->file, 'c+b');
        if ($handle === false) {
            throw new RuntimeException("Datei nicht beschreibbar: {$this->file}");
        }
        try {
            flock($handle, LOCK_EX);
            $data = self::decode((string) stream_get_contents($handle));
            $result = $mutation($data);

            // Erst kodieren, dann leeren – ein Fehler darf die Datei nicht zerstören.
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $json);
            fflush($handle);
            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function decode(string $contents): array
    {
        if (trim($contents) === '') {
            return self::emptyData();
        }
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        return [
            'next_id' => (int) ($data['next_id'] ?? 1),
            'rows'    => array_values($data['rows'] ?? []),
        ];
    }

    private static function emptyData(): array
    {
        return ['next_id' => 1, 'rows' => []];
    }
}
