<?php

namespace App\Http\Requests\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\Message;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Neue Unterhaltung: Agentur an eine Band – oder Band an die Agentur.
 */
class MessageThreadRequest extends FormRequest
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
            'band_id' => ['required', 'integer', $this->accessibleBand()],
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:'.Message::MAX_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'band_id.required' => 'Bitte eine Band wählen.',
            'band_id.integer' => 'Bitte eine Band wählen.',
            'subject.required' => 'Betreff fehlt.',
            'body.required' => 'Die Nachricht ist leer.',
            'body.max' => 'Die Nachricht ist zu lang (max. '.Message::MAX_LENGTH.' Zeichen).',
        ];
    }

    private function accessibleBand(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $band = Band::query()->find($value);

            if ($band === null || ! $this->user()->can('access', $band)) {
                $fail('Bitte eine Band wählen.');
            }
        };
    }
}
