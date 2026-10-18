<?php

namespace App\Http\Requests\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use App\Rules\NotExecutableFileRule;
use App\Services\BandManager\Images;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Eine Datei in den Cloud-Speicher einer Band hochladen (die Seite schickt mehrere Dateien nacheinander).
 * Den Speicherplatz prüft StoreCloudFileAction, Rechte und Band-Zugriff der Controller.
 */
class CloudFileUploadRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:'.$this->maxKilobytes(), new NotExecutableFileRule],
            'folder_id' => ['nullable', 'integer', $this->folderOfBand()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxMb = (int) floor($this->maxKilobytes() / 1024);

        return [
            'file.required' => "Bitte eine Datei auswählen (max. {$maxMb} MB).",
            'file.max' => "Die Datei ist zu groß (max. {$maxMb} MB).",
            'file.uploaded' => "Die Datei ist zu groß (max. {$maxMb} MB) oder der Upload wurde unterbrochen.",
        ];
    }

    public function folder(): ?CloudFolder
    {
        return $this->validated('folder_id') ? CloudFolder::query()->find($this->validated('folder_id')) : null;
    }

    private function maxKilobytes(): int
    {
        return app(Images::class)->maxUploadKilobytes((int) config('band-manager.cloud.max_file_mb'));
    }

    private function folderOfBand(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            /** @var Band $band */
            $band = $this->route('band');

            if (CloudFolder::query()->whereKey($value)->value('band_id') !== $band->id) {
                $fail('Der Ordner wurde nicht gefunden – vielleicht wurde er gerade gelöscht.');
            }
        };
    }
}
