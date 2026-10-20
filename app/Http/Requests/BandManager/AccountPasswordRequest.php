<?php

namespace App\Http\Requests\BandManager;

use App\Services\BandManager\Impersonation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Mein Konto → Passwort ändern (mit aktuellem Passwort). Beim „Einloggen als“ nicht erlaubt.
 */
class AccountPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! app(Impersonation::class)->isActive();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:band-manager'],
            'password' => ['required', 'string', 'min:'.config('band-manager.account.password_min'), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Bitte gib dein aktuelles Passwort ein.',
            'current_password.current_password' => 'Dein aktuelles Passwort ist nicht korrekt.',
            'password.required' => 'Bitte gib ein neues Passwort ein.',
            'password.min' => 'Das neue Passwort muss mindestens :min Zeichen lang sein.',
            'password.confirmed' => 'Die beiden neuen Passwörter stimmen nicht überein.',
        ];
    }
}
