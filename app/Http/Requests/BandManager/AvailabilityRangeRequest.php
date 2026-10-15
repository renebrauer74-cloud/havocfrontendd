<?php

namespace App\Http\Requests\BandManager;

use App\Models\BandManager\Band;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Zeitraum im Ampel-Kalender (from/to inklusiv, to optional = ein Tag).
 */
class AvailabilityRangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access', $this->band());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.*' => 'Ungültiger Zeitraum.',
            'to.*' => 'Ungültiger Zeitraum.',
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['from', 'to'])) {
                    return;
                }

                if ($this->from()->diffInDays($this->to()) > (int) config('band-manager.availability.max_range_days')) {
                    $validator->errors()->add('to', 'Zeitraum zu groß.');
                }
            },
        ];
    }

    public function band(): Band
    {
        return $this->route('band');
    }

    public function from(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->input('from'));
    }

    public function to(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->input('to') ?? $this->input('from'));
    }
}
