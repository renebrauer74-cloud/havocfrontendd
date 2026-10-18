<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Begrenzt die Pixelzahl, bevor die Conversion das Bild entpackt: Ein stark komprimiertes Bild mit
 * riesigen Maßen würde sonst den Speicher des Upload-Requests sprengen (GD braucht mehrere Byte pro Pixel).
 */
class MaxMegapixelsRule implements ValidationRule
{
    public function __construct(private int $maxMegapixels) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $imageSize = $value instanceof UploadedFile ? @getimagesize((string) $value->getRealPath()) : false;

        if ($imageSize === false) {
            $fail('Das Bild konnte nicht gelesen werden.');

            return;
        }

        if ($this->maxMegapixels < $imageSize[0] * $imageSize[1] / 1_000_000) {
            $fail("Das Bild ist zu groß (max. {$this->maxMegapixels} Megapixel).");
        }
    }
}
