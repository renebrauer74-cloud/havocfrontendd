<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\AreaAccessEnum;
use App\Enums\BandManager\BandAreaEnum;
use App\Enums\BandManager\UserRoleEnum;
use App\Models\BandManager\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Zugang einer Band (Bandmitglied, Team, Fotograf) anlegen/bearbeiten. Das Recht logins.manage prüft die Route,
 * dass der Zugang zur Band gehört die Route-Bindung.
 */
class BandAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access', $this->route('band'))
            && ($this->editedUser()?->role->isBandAccess() ?? true);
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
            'role' => ['required', Rule::enum(UserRoleEnum::class)->only(UserRoleEnum::bandAccessCases())],
            'is_active' => ['boolean'],
            'areas' => ['array:'.implode(',', array_column(BandAreaEnum::cases(), 'value'))],
            'areas.*' => ['nullable', Rule::enum(AreaAccessEnum::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'role' => 'Rolle',
            'areas' => 'Bereiche',
            'areas.*' => 'Bereich',
        ];
    }

    /**
     * Felder für das Model – das Passwort nur, wenn eines eingegeben wurde.
     *
     * @return array{name: string, email: string, role: string, is_active: bool, area_access: array<string, string>, password?: string}
     */
    public function userAttributes(): array
    {
        $attributes = $this->safe()->only(['name', 'email', 'role']) + [
            'is_active' => $this->boolean('is_active'),
            'area_access' => $this->areaAccess(),
        ];

        if ($this->filled('password')) {
            $attributes['password'] = $this->validated('password');
        }

        return $attributes;
    }

    /**
     * Freigegebene Bereiche ohne „kein Zugriff“. Bereiche ohne Bearbeiten-Stufe werden auf „Ansehen“ gesetzt.
     *
     * @return array<string, string>
     */
    private function areaAccess(): array
    {
        $areaAccess = [];

        foreach (array_filter($this->validated('areas', [])) as $area => $access) {
            $area = BandAreaEnum::from($area);
            $areaAccess[$area->value] = $area->hasEditLevel() ? $access : AreaAccessEnum::View->value;
        }

        return $areaAccess;
    }

    private function editedUser(): ?User
    {
        return $this->route('user');
    }
}
