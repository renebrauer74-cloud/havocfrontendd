<?php

namespace App\Models\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bild in der Spalte `image_url`, abgelegt auf der Disk "uploads" (public/uploads/<ordner>/).
 *
 * Gespeichert wird der relative Pfad inkl. "uploads/" – kompatibel zu den Daten der alten Plain-PHP-Version.
 * Beim Löschen des Datensatzes wird die Datei mit entfernt.
 */
trait HasUploadedImage
{
    public static function bootHasUploadedImage(): void
    {
        static::deleted(function (self $model): void {
            self::deleteUploadedImage($model->image_url);
        });
    }

    /**
     * Unterordner in public/uploads/ für die Bilder dieses Modells.
     */
    abstract protected function imageFolder(): string;

    /**
     * Speichert ein neues Bild und entfernt das bisherige. Der Datensatz selbst wird nicht gespeichert.
     */
    public function replaceImage(UploadedFile $file): void
    {
        $previous = $this->image_url;

        $this->image_url = 'uploads/'.$file->store($this->imageFolder(), 'uploads');

        self::deleteUploadedImage($previous);
    }

    /**
     * Löscht eine hochgeladene Datei – externe URLs und Pfade außerhalb von uploads/ werden ignoriert.
     */
    public static function deleteUploadedImage(?string $path): void
    {
        if ($path === null || ! Str::startsWith($path, 'uploads/') || Str::contains($path, '..')) {
            return;
        }

        Storage::disk('uploads')->delete(Str::after($path, 'uploads/'));
    }
}
