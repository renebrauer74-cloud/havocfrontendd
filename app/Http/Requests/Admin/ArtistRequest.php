<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class ArtistRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:65000'],
            'bio_en' => ['nullable', 'string', 'max:65000'],
            'spotify_url' => ['nullable', 'string', 'max:1024'],
            'youtube_url' => ['nullable', 'string', 'max:1024'],
            'website_url' => ['nullable', 'string', 'max:1024'],
            'instagram_url' => ['nullable', 'string', 'max:1024'],
            'tiktok_url' => ['nullable', 'string', 'max:1024'],
            'image' => $this->imageRules(),
        ];
    }
}
