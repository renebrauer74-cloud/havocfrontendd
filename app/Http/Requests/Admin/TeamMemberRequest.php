<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class TeamMemberRequest extends AdminFormRequest
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
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
            'hobbies' => ['nullable', 'string', 'max:65000'],
            'bio' => ['nullable', 'string', 'max:65000'],
            'image' => $this->imageRules(),
            'remove_image' => ['nullable', 'boolean'],
        ];
    }
}
