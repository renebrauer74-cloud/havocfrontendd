<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\CloudLinkTypeEnum;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Freigabe-Link anlegen: Laufzeit in Tagen aus config('band-manager.cloud.link_expiry_days') oder 0 = unbegrenzt.
 * Upload-Links (nur für Ordner) mit Hinweistext und der Wahl „nur Fotos & Videos“ oder alle Dateitypen.
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
            'type' => ['nullable', Rule::enum(CloudLinkTypeEnum::class)],
            'message' => ['nullable', 'string', 'max:500'],
            'allow_all_types' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expires_in.*' => 'Bitte eine Laufzeit für den Link wählen.',
            'type.*' => 'Bitte wählen, was Externe über den Link dürfen.',
            'message.max' => 'Der Hinweis ist zu lang (max. :max Zeichen).',
        ];
    }

    public function expiresAt(): ?CarbonInterface
    {
        $days = (int) $this->validated('expires_in');

        return $days > 0 ? now()->addDays($days)->endOfDay() : null;
    }

    public function type(): CloudLinkTypeEnum
    {
        return $this->enum('type', CloudLinkTypeEnum::class) ?? CloudLinkTypeEnum::Read;
    }
}
