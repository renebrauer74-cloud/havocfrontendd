<?php

namespace App\Http\Requests\BandManager;

use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Rules\NotExecutableFileRule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Datei im Cloud-Speicher umbenennen und/oder in einen anderen Ordner derselben Band verschieben.
 */
class CloudFileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:'.(int) config('band-manager.cloud.name_max'), 'not_in:.,..', new NotExecutableFileRule],
            'folder_id' => ['nullable', 'integer', $this->folderOfBand()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte einen Dateinamen eingeben.',
            'name.max' => 'Der Dateiname ist zu lang (max. :max Zeichen).',
            'name.not_in' => 'Dieser Dateiname ist nicht erlaubt.',
        ];
    }

    public function folder(): ?CloudFolder
    {
        return $this->validated('folder_id') ? CloudFolder::query()->find($this->validated('folder_id')) : null;
    }

    private function folderOfBand(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            /** @var CloudFile $file */
            $file = $this->route('file');

            if (CloudFolder::query()->whereKey($value)->value('band_id') !== $file->band_id) {
                $fail('Der gewählte Ordner gehört nicht zu dieser Band.');
            }
        };
    }
}
