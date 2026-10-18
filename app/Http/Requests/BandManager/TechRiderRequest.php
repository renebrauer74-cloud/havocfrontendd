<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\BacklineProviderEnum;
use App\Enums\BandManager\MicStandEnum;
use App\Enums\BandManager\MonitorTypeEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\TechRider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tech Rider einer Band. Das Recht riders.edit prüft die Route.
 */
class TechRiderRequest extends FormRequest
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
        $maxRows = (int) config('band-manager.tech_rider.max_rows');
        $text = ['nullable', 'string', 'max:'.(int) config('band-manager.tech_rider.text_max')];
        $short = ['nullable', 'string', 'max:150'];
        $note = ['nullable', 'string', 'max:255'];
        $minutes = ['nullable', 'integer', 'min:0', 'max:1440'];
        $meters = ['nullable', 'numeric', 'min:0', 'max:999'];

        return [
            'contact_name' => $short,
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'brings_foh_engineer' => ['boolean'],
            'brings_monitor_engineer' => ['boolean'],

            'setup_minutes' => $minutes,
            'soundcheck_minutes' => $minutes,
            'show_minutes' => $minutes,
            'teardown_minutes' => $minutes,

            'line_up' => ['nullable', 'array', 'max:'.$maxRows],
            'line_up.*.name' => $short,
            'line_up.*.instrument' => $short,

            'inputs' => ['nullable', 'array', 'max:'.$maxRows],
            'inputs.*.source' => $short,
            'inputs.*.mic' => $short,
            'inputs.*.stand' => ['nullable', Rule::enum(MicStandEnum::class)],
            'inputs.*.phantom' => ['boolean'],
            'inputs.*.note' => $note,

            'monitors' => ['nullable', 'array', 'max:'.$maxRows],
            'monitors.*.who' => $short,
            'monitors.*.type' => ['nullable', Rule::enum(MonitorTypeEnum::class)],
            'monitors.*.note' => $note,

            'backline' => ['nullable', 'array', 'max:'.$maxRows],
            'backline.*.item' => ['nullable', 'string', 'max:255'],
            'backline.*.provider' => ['nullable', Rule::enum(BacklineProviderEnum::class)],
            'backline.*.note' => $note,

            'custom_sections' => ['nullable', 'array', 'max:'.$maxRows],
            'custom_sections.*.title' => $short,
            'custom_sections.*.content' => $text,

            'pa_notes' => $text,
            'monitor_notes' => $text,
            'stage_width' => $meters,
            'stage_depth' => $meters,
            'stage_notes' => $text,
            'lighting_notes' => $text,
            'power_notes' => $text,
            'notes' => $text,

            'stage_plot' => ['nullable', 'file', 'mimetypes:'.implode(',', array_keys(TechRider::STAGE_PLOT_TYPES)), 'image', 'max:'.$this->maxStagePlotMb() * 1024],
            'remove_stage_plot' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = $this->maxStagePlotMb();
        $types = 'Der Bühnenplan muss ein PNG- oder JPG-Bild sein (PDF bitte unter „Dokumente“ hochladen).';

        return [
            'stage_plot.max' => "Der Bühnenplan ist zu groß (max. {$maxMb} MB).",
            'stage_plot.uploaded' => "Der Bühnenplan ist zu groß (max. {$maxMb} MB) oder konnte nicht hochgeladen werden.",
            'stage_plot.mimetypes' => $types,
            'stage_plot.image' => $types,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_name' => 'Ansprechpartner',
            'contact_phone' => 'Telefon',
            'contact_email' => 'E-Mail',
            'stage_width' => 'Bühnenbreite',
            'stage_depth' => 'Bühnentiefe',
            'line_up.*.name' => 'Name (Besetzung)',
            'line_up.*.instrument' => 'Instrument (Besetzung)',
            'inputs.*.source' => 'Instrument / Quelle (Kanalliste)',
            'inputs.*.mic' => 'Mikrofon / DI (Kanalliste)',
            'inputs.*.note' => 'Bemerkung (Kanalliste)',
            'monitors.*.who' => 'Für wen (Monitoring)',
            'monitors.*.note' => 'Mix-Wunsch (Monitoring)',
            'backline.*.item' => 'Gerät (Backline)',
            'backline.*.note' => 'Bemerkung (Backline)',
            'custom_sections.*.title' => 'Titel (eigener Abschnitt)',
            'custom_sections.*.content' => 'Inhalt (eigener Abschnitt)',
        ];
    }

    public function band(): Band
    {
        return $this->route('band');
    }

    private function maxStagePlotMb(): int
    {
        return (int) config('band-manager.tech_rider.max_stage_plot_mb');
    }
}
