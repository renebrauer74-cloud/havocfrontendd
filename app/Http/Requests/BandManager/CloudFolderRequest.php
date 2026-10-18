<?php

namespace App\Http\Requests\BandManager;

use App\Actions\BandManager\Cloud\ListCloudSubfolderIdsAction;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ordner im Cloud-Speicher anlegen (Route mit {band}) bzw. umbenennen/verschieben (Route mit {folder}).
 * Rechte und Band-Zugriff prüft der Controller.
 */
class CloudFolderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.(int) config('band-manager.cloud.name_max'), 'not_in:.,..', 'regex:/^[^\\\\\/:*?"<>|\p{C}]+$/u', $this->freeName()],
            'parent_id' => ['nullable', 'integer', $this->targetFolder()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte einen Ordnernamen eingeben.',
            'name.max' => 'Der Ordnername ist zu lang (max. :max Zeichen).',
            'name.not_in' => 'Dieser Ordnername ist nicht erlaubt.',
            'name.regex' => 'Der Ordnername darf keine der Zeichen \\ / : * ? " < > | enthalten.',
        ];
    }

    public function band(): Band
    {
        return $this->route('band') ?? $this->route('folder')->band;
    }

    public function parentFolder(): ?CloudFolder
    {
        return $this->validated('parent_id') ? CloudFolder::query()->find($this->validated('parent_id')) : null;
    }

    /**
     * Kein zweiter Ordner mit gleichem Namen am selben Ort – sonst wären sie im ZIP nicht zu unterscheiden.
     */
    private function freeName(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $taken = CloudFolder::query()
                ->where('band_id', $this->band()->id)
                ->where('parent_id', $this->input('parent_id') ?: null)
                ->where('name', $value)
                ->when($this->route('folder'), fn ($query, CloudFolder $folder) => $query->whereKeyNot($folder->id))
                ->exists();

            if ($taken) {
                $fail("Hier gibt es schon einen Ordner „{$value}“.");
            }
        };
    }

    /**
     * Zielordner derselben Band – beim Verschieben nicht der Ordner selbst oder einer seiner Unterordner.
     */
    private function targetFolder(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (CloudFolder::query()->whereKey($value)->value('band_id') !== $this->band()->id) {
                $fail('Der gewählte Ordner gehört nicht zu dieser Band.');

                return;
            }

            $folder = $this->route('folder');

            if ($folder instanceof CloudFolder && in_array((int) $value, app(ListCloudSubfolderIdsAction::class)->execute($folder), true)) {
                $fail('Ein Ordner kann nicht in sich selbst oder einen seiner Unterordner verschoben werden.');
            }
        };
    }
}
