<?php
declare(strict_types=1);

namespace Havoc;

use RuntimeException;

/** Bild-Uploads nach public/uploads/<ordner>/ – gespeichert wird der relative Pfad. */
final class Upload
{
    private const MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * @return string|null relativer Pfad (z.B. "uploads/artists/ab12.jpg") oder null, wenn kein Bild gewählt wurde
     * @throws RuntimeException bei ungültigen Dateien
     */
    public static function image(string $field, string $folder): ?string
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Das Bild ist zu groß.',
                default => 'Upload fehlgeschlagen (Fehlercode ' . $file['error'] . ').',
            });
        }
        if ($file['size'] > (int) config('uploads.max_bytes', 8 * 1024 * 1024)) {
            throw new RuntimeException('Das Bild ist zu groß.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extension = self::MIME_TYPES[$mime] ?? null;
        if ($extension === null) {
            throw new RuntimeException('Bitte nur Bilder hochladen (JPG, PNG, GIF, WebP).');
        }

        $relativeDir = 'uploads/' . $folder;
        $targetDir = PUBLIC_PATH . '/' . $relativeDir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Upload-Ordner kann nicht angelegt werden.');
        }

        $filename = bin2hex(random_bytes(12)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
            throw new RuntimeException('Bild konnte nicht gespeichert werden.');
        }

        return $relativeDir . '/' . $filename;
    }

    /** Löscht eine hochgeladene Datei – nur innerhalb von public/uploads/. */
    public static function delete(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with($relativePath, 'uploads/')) {
            return;
        }
        $uploadsRoot = realpath(PUBLIC_PATH . '/uploads');
        $file = realpath(PUBLIC_PATH . '/' . $relativePath);
        if ($uploadsRoot && $file && str_starts_with($file, $uploadsRoot . DIRECTORY_SEPARATOR) && is_file($file)) {
            unlink($file);
        }
    }
}
