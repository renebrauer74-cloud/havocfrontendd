<?php

namespace App\Http\Requests\BandManager;

use App\Services\BandManager\Impersonation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mein Konto → neue E-Mail-Adresse anfordern (mit aktuellem Passwort). Beim „Einloggen als“ nicht erlaubt.
 */
class AccountEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! app(Impersonation::class)->isActive();
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
            'email' => ['bail', 'required', 'string', 'email', 'max:190', Rule::notIn([$this->user()->email]), Rule::unique('bm_users', 'email')],
            'email_password' => ['required', 'string', 'current_password:band-manager'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Bitte gib die neue E-Mail-Adresse ein.',
            'email.not_in' => 'Das ist bereits deine E-Mail-Adresse.',
            'email.unique' => 'Diese E-Mail-Adresse wird schon für einen anderen Login verwendet.',
            'email_password.required' => 'Bitte gib zur Sicherheit dein aktuelles Passwort ein.',
            'email_password.current_password' => 'Dein aktuelles Passwort ist nicht korrekt.',
        ];
    }
}
