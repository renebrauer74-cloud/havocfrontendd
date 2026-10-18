<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\AvailabilityStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Kalendereintrag speichern: Status, optional Titel (Pflicht bei „Sonstiges“), Uhrzeiten oder ganztägig, Notiz.
 */
class StoreAvailabilityRequest extends AvailabilityRangeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allDay = $this->isAllDay();

        return parent::rules() + [
            'status' => ['required', Rule::enum(AvailabilityStatusEnum::class)],
            'title' => [Rule::requiredIf(fn (): bool => (bool) $this->status()?->requiresTitle()), 'nullable', 'string', 'max:100'],
            'all_day' => ['boolean'],
            'start_time' => $allDay ? ['exclude'] : ['required', 'date_format:H:i'],
            'end_time' => $allDay ? ['exclude'] : ['nullable', 'date_format:H:i', 'after:start_time'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'status.*' => 'Ungültiger Status.',
            'title.required' => 'Bitte eintragen, was ansteht (z. B. Musikvideo, Fotoshooting).',
            'title.max' => 'Der Titel darf höchstens 100 Zeichen lang sein.',
            'start_time.*' => 'Bitte eine gültige Startzeit angeben oder „Ganztägig“ wählen.',
            'end_time.*' => 'Die Endzeit muss nach der Startzeit liegen.',
        ];
    }

    public function status(): ?AvailabilityStatusEnum
    {
        return $this->enum('status', AvailabilityStatusEnum::class);
    }

    /**
     * Ohne Angabe ganztägig – so bleiben bisherige Aufrufe (nur Status + Notiz) gültig.
     */
    public function isAllDay(): bool
    {
        return $this->boolean('all_day', true);
    }

    public function title(): ?string
    {
        return $this->validated('title') ?: null;
    }

    public function startTime(): ?string
    {
        return $this->validated('start_time');
    }

    public function endTime(): ?string
    {
        return $this->validated('end_time') ?: null;
    }
}
