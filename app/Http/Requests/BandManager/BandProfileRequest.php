<?php

namespace App\Http\Requests\BandManager;

use App\Actions\BandManager\Profile\NormalizeProfileLinkAction;
use App\Enums\BandManager\PlatformEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\BandProfile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bandprofil: Logo, Biografie (DE/EN), Social Media & Streaming. Das Recht team.edit prüft die Route.
 */
class BandProfileRequest extends FormRequest
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
        $bioMax = (int) config('band-manager.team.bio_max');

        $rules = [
            'bio_de' => ['nullable', 'string', 'max:'.$bioMax],
            'bio_en' => ['nullable', 'string', 'max:'.$bioMax],
            'logo' => ['nullable', 'file', 'mimetypes:'.implode(',', array_keys(BandProfile::LOGO_TYPES)), 'image', 'max:'.$this->maxLogoMb() * 1024],
            'remove_logo' => ['boolean'],
        ];

        foreach (PlatformEnum::cases() as $platform) {
            $rules['link_'.$platform->value] = ['nullable', 'string', 'max:255', $this->validLink($platform)];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = $this->maxLogoMb();
        $types = 'Als Logo sind nur PNG-, JPG- oder WebP-Dateien erlaubt (am besten PNG mit transparentem Hintergrund).';

        return [
            'logo.max' => "Das Logo ist zu groß (max. {$maxMb} MB).",
            'logo.uploaded' => "Das Logo ist zu groß (max. {$maxMb} MB) oder konnte nicht hochgeladen werden.",
            'logo.mimetypes' => $types,
            'logo.image' => $types,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['bio_de' => 'Biografie (Deutsch)', 'bio_en' => 'Biografie (Englisch)'];
    }

    /**
     * Ausgefüllte Links, normalisiert ("@name" → vollständige Adresse).
     *
     * @return array<string, string>
     */
    public function links(): array
    {
        $links = [];

        foreach (PlatformEnum::cases() as $platform) {
            $value = $this->validated('link_'.$platform->value);

            if (filled($value)) {
                $links[$platform->value] = app(NormalizeProfileLinkAction::class)->execute($value, $platform);
            }
        }

        return $links;
    }

    public function band(): Band
    {
        return $this->route('band');
    }

    private function validLink(PlatformEnum $platform): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($platform): void {
            if (app(NormalizeProfileLinkAction::class)->execute((string) $value, $platform) === null) {
                $fail("{$platform->label()}: Bitte einen gültigen Link eingeben (z. B. https://…).");
            }
        };
    }

    private function maxLogoMb(): int
    {
        return (int) config('band-manager.team.max_logo_mb');
    }
}
