<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class TourDateRequest extends AdminFormRequest
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
            'date' => ['required', 'string', 'max:64'],
            'city' => ['required', 'string', 'max:255'],
            'venue' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
