<?php

namespace App\Services\BandManager;

/**
 * Upload-Grenzen des Servers für die Bild-Uploads (Verkleinern übernehmen die Media-Library-Conversions).
 */
class Images
{
    /**
     * Max. Dateigröße in KB: das eigene Limit, höchstens aber upload_max_filesize des Servers.
     */
    public function maxUploadKilobytes(int $ownMegabytes): int
    {
        $own = $ownMegabytes * 1024;
        $ini = (int) floor($this->iniBytes((string) ini_get('upload_max_filesize')) / 1024);

        return $ini > 0 ? min($own, $ini) : $own;
    }

    /**
     * '8M' → 8388608, '1G' → 1073741824, '-1'/'0' → 0 (= unbegrenzt).
     */
    public function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $number = (float) $value;

        return (int) match (strtoupper(substr($value, -1))) {
            'G' => $number * 1073741824,
            'M' => $number * 1048576,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
