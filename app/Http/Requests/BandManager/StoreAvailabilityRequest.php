<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\AvailabilityStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreAvailabilityRequest extends AvailabilityRangeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'status' => ['required', Rule::enum(AvailabilityStatusEnum::class)],
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
        ];
    }
}
