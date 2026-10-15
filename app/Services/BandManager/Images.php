<?php

namespace App\Services\BandManager;

/**
 * Bildverarbeitung mit GD für Team-Fotos und Galerie-Vorschauen.
 */
class Images
{
    /**
     * Verkleinert auf max. $maxPx (längste Seite) und speichert als JPEG – transparente Bereiche werden weiß,
     * Handyfotos werden laut EXIF gedreht. false = nicht möglich (kein GD, zu groß, kaputt).
     */
    public function resizeToJpeg(string $source, string $mime, string $target, int $maxPx, int $maxPixels = 150_000_000): bool
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        $info = @getimagesize($source);
        $pixels = $info ? $info[0] * $info[1] : 0;

        if (! $pixels || $pixels > $maxPixels) {
            return false;
        }

        // GD braucht ca. 5 Byte pro Pixel (+ Reserve)
        $this->ensureMemory($pixels * 5 + 64 * 1048576);

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };

        if (! $image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxPx / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $output = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
        imagecopyresampled($output, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        unset($image);   // großes Original sofort aus dem Speicher werfen

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($source);
            $angle = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;

            if ($angle) {
                $output = imagerotate($output, $angle, 0) ?: $output;
            }
        }

        $directory = dirname($target);
        if (! is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        return imagejpeg($output, $target, 85);
    }

    /**
     * Breite und Höhe in Pixeln oder [null, null].
     *
     * @return array{0: ?int, 1: ?int}
     */
    public function dimensions(string $path): array
    {
        $info = @getimagesize($path);

        return $info ? [(int) $info[0], (int) $info[1]] : [null, null];
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

    private function ensureMemory(int $neededBytes): void
    {
        $limit = $this->iniBytes((string) ini_get('memory_limit'));
        $wanted = memory_get_usage() + $neededBytes;

        if ($limit !== 0 && $limit < $wanted) {
            @ini_set('memory_limit', (int) ceil($wanted / 1048576).'M');
        }
    }
}
