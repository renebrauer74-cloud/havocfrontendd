<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\CateringBuyoutEnum;
use App\Enums\BandManager\CateringMealEnum;
use App\Models\BandManager\Band;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Catering Rider einer Band. Das Recht riders.edit prüft die Route.
 */
class CateringRiderRequest extends FormRequest
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
        $maxRows = (int) config('band-manager.catering_rider.max_rows');
        $text = ['nullable', 'string', 'max:'.(int) config('band-manager.catering_rider.text_max')];
        $short = ['nullable', 'string', 'max:150'];
        $note = ['nullable', 'string', 'max:255'];
        $count = ['nullable', 'integer', 'min:0', 'max:255'];

        $rules = [
            'contact_name' => $short,
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:190'],

            'people' => ['nullable', 'array', 'max:'.$maxRows],
            'people.*.name' => $short,
            'people.*.role' => $short,
            'people.*.diet' => $note,
            'people.*.allergy' => $note,
            'diet_notes' => $text,

            'meals' => ['nullable', 'array'],

            'buyout' => ['required', Rule::enum(CateringBuyoutEnum::class)],
            'buyout_amount' => ['nullable', 'numeric', 'min:0', 'max:'.(int) config('band-manager.catering_rider.max_buyout')],
            'buyout_notes' => $text,

            'drinks' => ['nullable', 'array', 'max:'.$maxRows],
            'drinks.*.item' => $note,
            'drinks.*.amount' => $short,
            'drinks.*.note' => $note,
            'drink_notes' => $text,

            'dressing_rooms' => $count,
            'stage_towels' => $count,
            'shower_towels' => $count,
            'needs_shower' => ['boolean'],
            'dressing_room_notes' => $text,

            'custom_sections' => ['nullable', 'array', 'max:'.$maxRows],
            'custom_sections.*.title' => $short,
            'custom_sections.*.content' => $text,

            'notes' => $text,
        ];

        foreach (CateringMealEnum::cases() as $meal) {
            $rules['meals.'.$meal->value.'.wanted'] = ['boolean'];
            $rules['meals.'.$meal->value.'.time'] = $short;
            $rules['meals.'.$meal->value.'.wishes'] = $text;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'contact_name' => 'Ansprechpartner',
            'contact_phone' => 'Telefon',
            'contact_email' => 'E-Mail',
            'buyout_amount' => 'Buyout pro Person',
            'dressing_rooms' => 'Anzahl Garderoben',
            'stage_towels' => 'Bühnenhandtücher',
            'shower_towels' => 'Duschhandtücher',
            'people.*.name' => 'Name (Reisegruppe)',
            'people.*.role' => 'Funktion (Reisegruppe)',
            'people.*.diet' => 'Ernährung (Reisegruppe)',
            'people.*.allergy' => 'Allergien (Reisegruppe)',
            'drinks.*.item' => 'Getränk',
            'drinks.*.amount' => 'Menge (Getränke)',
            'drinks.*.note' => 'Bemerkung (Getränke)',
            'custom_sections.*.title' => 'Titel (eigener Abschnitt)',
            'custom_sections.*.content' => 'Inhalt (eigener Abschnitt)',
        ];

        foreach (CateringMealEnum::cases() as $meal) {
            $attributes['meals.'.$meal->value.'.time'] = 'Zeitpunkt ('.$meal->label().')';
            $attributes['meals.'.$meal->value.'.wishes'] = 'Wünsche ('.$meal->label().')';
        }

        return $attributes;
    }

    public function band(): Band
    {
        return $this->route('band');
    }
}
