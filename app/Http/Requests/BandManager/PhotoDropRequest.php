<?php

namespace App\Http\Requests\BandManager;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Photo Drop anlegen: Name = neuer Ordner (Regeln wie CloudFolderRequest), dazu Hinweistext, Laufzeit
 * (config('band-manager.cloud.link_expiry_days') oder 0 = unbegrenzt) und „alle Dateitypen“.
 */
class PhotoDropRequest extends CloudFolderRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'message' => ['nullable', 'string', 'max:500'],
            'expires_in' => ['required', 'integer', Rule::in([0, ...(array) config('band-manager.cloud.link_expiry_days')])],
            'allow_all_types' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'name.required' => 'Bitte gib dem Photo Drop einen Namen, z. B. „Gig Gloria Köln“.',
            'message.max' => 'Der Hinweis ist zu lang (max. :max Zeichen).',
            'expires_in.*' => 'Bitte eine Laufzeit für den Link wählen.',
        ];
    }

    public function expiresAt(): ?CarbonInterface
    {
        $days = (int) $this->validated('expires_in');

        return $days > 0 ? now()->addDays($days)->endOfDay() : null;
    }
}
