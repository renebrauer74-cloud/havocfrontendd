<?php

namespace App\Http\Requests\Admin;

use App\Models\GalleryImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class GalleryImageRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'integer', 'exists:artists,id'],
            'image' => $this->imageRules(required: true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Bitte ein Bild auswählen.',
        ];
    }

    /**
     * Höchstens GalleryImage::MAX_PER_ARTIST Bilder pro Band.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $count = GalleryImage::query()->where('artist_id', $this->integer('artist_id'))->count();

                if ($count >= GalleryImage::MAX_PER_ARTIST) {
                    $validator->errors()->add('image', 'Maximal '.GalleryImage::MAX_PER_ARTIST.' Galeriebilder pro Band.');
                }
            },
        ];
    }
}
