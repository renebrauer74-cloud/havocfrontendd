<?php

namespace App\Http\Requests\BandManager\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Band anlegen/bearbeiten. Zugriff nur für Admins (Gate band-manager.admin an der Route).
 */
class BandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('contact_email'))) {
            $this->merge(['contact_email' => Str::lower($this->input('contact_email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'genre' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:65000'],
            'contact_email' => ['nullable', 'string', 'email', 'max:190'],
            'members' => ['array'],
            'members.*' => ['integer', Rule::exists('bm_users', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Bandname',
            'description' => 'Beschreibung',
            'contact_email' => 'Kontakt-E-Mail',
            'members' => 'Benutzer',
            'members.*' => 'Benutzer',
        ];
    }

    /**
     * @return array{name: string, genre: ?string, description: ?string, contact_email: ?string}
     */
    public function bandAttributes(): array
    {
        return $this->safe()->only(['name', 'genre', 'description', 'contact_email']);
    }

    /**
     * @return list<int>
     */
    public function userIds(): array
    {
        return array_map('intval', $this->validated('members', []));
    }
}
