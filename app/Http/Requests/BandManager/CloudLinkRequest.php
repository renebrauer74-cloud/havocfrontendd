<?php

namespace App\Http\Requests\BandManager;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Freigabe-Link anlegen: Laufzeit in Tagen aus config('band-manager.cloud.link_expiry_days') oder 0 = unbegrenzt.
 */
class CloudLinkRequest extends FormRequest
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
            'expires_in' => ['required', 'integer', Rule::in([0, ...(array) config('band-manager.cloud.link_expiry_days')])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expires_in.*' => 'Bitte eine Laufzeit für den Link wählen.',
        ];
    }

    public function expiresAt(): ?CarbonInterface
    {
        $days = (int) $this->validated('expires_in');

        return $days > 0 ? now()->addDays($days)->endOfDay() : null;
    }
}
