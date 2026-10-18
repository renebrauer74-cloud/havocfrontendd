<?php

namespace App\Http\Requests\BandManager\Admin;

use App\Enums\BandManager\UserRoleEnum;
use App\Models\BandManager\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Benutzer anlegen/bearbeiten. Zugriff nur für Admins (Gate band-manager.admin an der Route).
 */
class UserRequest extends FormRequest
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
            $this->merge(['email' => Str::lower($this->input('email'))]);
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
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('bm_users')->ignore($this->editedUser())],
            'password' => [$this->editedUser() ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', $this->editedUser()
                ? Rule::enum(UserRoleEnum::class)
                : Rule::enum(UserRoleEnum::class)->only([UserRoleEnum::Admin, UserRoleEnum::Artist])],
            'is_active' => ['boolean'],
            'band_name' => [Rule::requiredIf(fn (): bool => $this->isNewArtist()), 'nullable', 'string', 'max:150'],
            'bands' => ['array'],
            'bands.*' => ['integer', Rule::exists('bm_bands', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'role' => 'Rolle',
            'band_name' => 'Band-/Künstlername',
            'bands' => 'Bands',
            'bands.*' => 'Band',
        ];
    }

    /**
     * Felder für das Model – das Passwort nur, wenn eines eingegeben wurde.
     *
     * @return array{name: string, email: string, role: string, is_active: bool, password?: string}
     */
    public function userAttributes(): array
    {
        $attributes = $this->safe()->only(['name', 'email', 'role']) + ['is_active' => $this->boolean('is_active')];

        if ($this->filled('password')) {
            $attributes['password'] = $this->validated('password');
        }

        // Admin kann sich nicht selbst aussperren
        if ($this->editedUser()?->is($this->user())) {
            $attributes['role'] = UserRoleEnum::Admin;
            $attributes['is_active'] = true;
        }

        return $attributes;
    }

    /**
     * @return list<int>
     */
    public function bandIds(): array
    {
        return array_map('intval', $this->validated('bands', []));
    }

    /**
     * Name der Band, die für einen neuen Künstler direkt angelegt wird.
     */
    public function bandName(): ?string
    {
        return $this->isNewArtist() ? $this->validated('band_name') : null;
    }

    private function isNewArtist(): bool
    {
        return ! $this->editedUser() && $this->enum('role', UserRoleEnum::class) === UserRoleEnum::Artist;
    }

    private function editedUser(): ?User
    {
        return $this->route('user');
    }
}
