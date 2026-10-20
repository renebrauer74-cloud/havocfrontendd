<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\UserRoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Mein Konto → Stammdaten: Name des Logins, beim Haupt-Login der Band auch die Bandnamen.
 */
class AccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'band_names' => ['array'],
            'band_names.*' => ['required', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['band_names.*' => 'Bandname'];
    }

    /**
     * Bandname je Band-ID – nur der Haupt-Login der Band darf ihn ändern, fremde Bands verwirft die Action.
     *
     * @return array<int, string>
     */
    public function bandNames(): array
    {
        if ($this->user()->role !== UserRoleEnum::Artist) {
            return [];
        }

        return collect($this->validated('band_names', []))
            ->mapWithKeys(fn (string $name, int|string $bandId): array => [(int) $bandId => $name])
            ->all();
    }
}
