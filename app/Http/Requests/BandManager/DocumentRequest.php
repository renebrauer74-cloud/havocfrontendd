<?php

namespace App\Http\Requests\BandManager;

use App\Enums\BandManager\RiderCategoryEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\Document;
use App\Models\BandManager\Gig;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vertrag (nur Agentur) oder Rider (Bands und Agentur) als PDF hochladen.
 */
class DocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return match ($this->input('type')) {
            Document::TYPE_CONTRACT => $user->can('documents.manage'),
            Document::TYPE_RIDER => $user->can('documents.upload') || $user->can('documents.manage'),
            default => true,   // ungültiger Typ → Validierungsfehler
        };
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isRider = $this->input('type') === Document::TYPE_RIDER;

        return [
            'type' => ['required', Rule::in([Document::TYPE_CONTRACT, Document::TYPE_RIDER])],
            'band_id' => ['required', 'integer', $this->accessibleBand()],
            'category' => $isRider ? ['required', Rule::enum(RiderCategoryEnum::class)] : ['exclude'],
            'gig_id' => $isRider ? ['exclude'] : ['nullable', 'integer', $this->gigOfBand()],
            'title' => ['nullable', 'string', 'max:190'],
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.$this->maxMb() * 1024],
            'filter' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = $this->maxMb();

        return [
            'type.*' => 'Ungültiger Dokumenttyp.',
            'band_id.required' => 'Bitte eine Band wählen.',
            'band_id.integer' => 'Bitte eine Band wählen.',
            'category.*' => 'Bitte die Art des Riders wählen.',
            'file.required' => 'Bitte eine PDF-Datei auswählen.',
            'file.max' => "Die Datei ist zu groß (max. {$maxMb} MB).",
            'file.uploaded' => "Die Datei ist zu groß (max. {$maxMb} MB) oder konnte nicht hochgeladen werden.",
            'file.mimetypes' => 'Nur PDF-Dateien sind erlaubt.',
        ];
    }

    /**
     * Titel aus dem Formular – sonst der Dateiname ohne Endung.
     */
    public function title(): string
    {
        return $this->validated('title') ?: mb_substr(pathinfo($this->originalName(), PATHINFO_FILENAME), 0, 190);
    }

    public function originalName(): string
    {
        return mb_substr(basename(str_replace('\\', '/', $this->file('file')->getClientOriginalName())), 0, 255) ?: 'dokument.pdf';
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

    private function gigOfBand(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (Gig::query()->whereKey($value)->value('band_id') !== (int) $this->input('band_id')) {
                $fail('Der gewählte Gig gehört nicht zu dieser Band.');
            }
        };
    }

    private function maxMb(): int
    {
        return (int) config('band-manager.documents.max_size_mb');
    }
}
