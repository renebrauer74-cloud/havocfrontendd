<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\FileAdder;

/**
 * Sperrt ausführbare Dateien im Cloud-Speicher (config('band-manager.cloud.blocked_extensions') und die
 * Sperrliste der Media Library). Geprüft wird jede Endung im Namen – auch „setup.exe.zip“ fällt durch,
 * weil „exe“ vorkommt. Gilt für Uploads (Original-Dateiname) und fürs Umbenennen (Text).
 */
class NotExecutableFileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = $value instanceof UploadedFile ? $value->getClientOriginalName() : (string) $value;
        $extensions = array_slice(explode('.', mb_strtolower(basename(str_replace('\\', '/', $name)))), 1);

        $blocked = array_intersect($extensions, [
            ...(array) config('band-manager.cloud.blocked_extensions'),
            ...(array) config('media-library.disallowed_extensions', FileAdder::$defaultDisallowedExtensions),
        ]);

        if ($blocked !== []) {
            $fail('„'.$name.'“: Ausführbare Dateien (.'.reset($blocked).') sind im Cloud-Speicher nicht erlaubt.');
        }
    }
}
