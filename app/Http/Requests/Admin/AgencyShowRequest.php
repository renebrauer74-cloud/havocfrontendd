<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class AgencyShowRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'string', 'max:64'],
            'city' => ['required', 'string', 'max:255'],
            'venue' => ['required', 'string', 'max:255'],
            'ticket_url' => ['nullable', 'string', 'max:1024'],
            'info_text' => ['nullable', 'string', 'max:65000'],
            'image' => $this->imageRules(),
        ];
    }
}
