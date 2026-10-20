<?php

namespace App\Http\Requests\BandManager;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * „Passwort vergessen“: neues Passwort über den Link aus der E-Mail.
 */
class ResetPasswordRequest extends FormRequest
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
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:'.config('band-manager.account.password_min'), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Bitte gib ein neues Passwort ein.',
            'password.min' => 'Das neue Passwort muss mindestens :min Zeichen lang sein.',
            'password.confirmed' => 'Die beiden neuen Passwörter stimmen nicht überein.',
        ];
    }
}
