<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\StagePlotElementEnum;
use App\Models\BandManager\Band;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Stage Plot einer Band. Das Recht riders.edit prüft die Route.
 * Die Elemente kommen als JSON-Text (Feld "items"), das gezeichnete Bild als PNG-data-URI (Feld "image").
 */
class StagePlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('access', $this->band());
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('items'))) {
            $this->merge(['items' => json_decode($this->input('items'), true) ?? 'ungültig']);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $minStage = (int) config('band-manager.stage_plot.min_stage_m');
        $maxStage = (int) config('band-manager.stage_plot.max_stage_m');
        $maxCm = $maxStage * 100;
        $maxImageChars = (int) config('band-manager.stage_plot.max_image_mb') * 1048576 * 4 / 3;

        return [
            'stage_width' => ['required', 'numeric', 'min:'.$minStage, 'max:'.$maxStage],
            'stage_depth' => ['required', 'numeric', 'min:'.$minStage, 'max:'.$maxStage],

            'items' => ['nullable', 'array', 'max:'.(int) config('band-manager.stage_plot.max_items')],
            'items.*' => ['array'],
            'items.*.type' => ['required', Rule::enum(StagePlotElementEnum::class)],
            'items.*.x' => ['required', 'numeric', 'min:-200', 'max:'.($maxCm + 200)],
            'items.*.y' => ['required', 'numeric', 'min:-200', 'max:'.($maxCm + 200)],
            'items.*.w' => ['required', 'numeric', 'min:0', 'max:'.$maxCm],
            'items.*.h' => ['required', 'numeric', 'min:1', 'max:'.$maxCm],
            'items.*.rotation' => ['required', 'numeric', 'min:-360', 'max:360'],
            'items.*.label' => ['nullable', 'string', 'max:80'],

            'notes' => ['nullable', 'string', 'max:'.(int) config('band-manager.stage_plot.text_max')],

            'image' => ['nullable', 'string', 'max:'.(int) $maxImageChars, $this->isPngDataUri(...)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.max' => 'Das Bild des Stage Plots ist zu groß (max. '.(int) config('band-manager.stage_plot.max_image_mb').' MB).',
            'items.array' => 'Die Elemente des Stage Plots konnten nicht gelesen werden – bitte die Seite neu laden.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'stage_width' => 'Bühnenbreite',
            'stage_depth' => 'Bühnentiefe',
            'items' => 'Elemente',
            'items.*.type' => 'Symbol',
            'items.*.label' => 'Beschriftung',
            'notes' => 'Hinweise',
            'image' => 'Bild',
        ];
    }

    public function band(): Band
    {
        return $this->route('band');
    }

    /**
     * Nur echte PNG-Bilder als data-URI ("data:image/png;base64,…").
     */
    private function isPngDataUri(string $attribute, mixed $value, Closure $fail): void
    {
        $prefix = 'data:image/png;base64,';
        $binary = is_string($value) && str_starts_with($value, $prefix) ? base64_decode(substr($value, strlen($prefix)), true) : false;
        $imageSize = $binary !== false ? @getimagesizefromstring($binary) : false;

        if ($imageSize === false || $imageSize['mime'] !== 'image/png') {
            $fail('Das Bild des Stage Plots konnte nicht erstellt werden – bitte erneut speichern.');
        }
    }
}
