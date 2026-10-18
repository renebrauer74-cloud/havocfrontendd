<?php

namespace App\Http\Requests\BandManager;

use App\Actions\BandManager\Cloud\CheckCloudLinkAccessAction;
use App\Models\BandManager\CloudLink;
use App\Rules\NotExecutableFileRule;
use App\Services\BandManager\Images;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Eine Datei über einen Upload-Link hochladen (ohne Login). Ohne „alle Dateitypen“ am Link nur
 * config('band-manager.cloud.link_upload_types'), ausführbare Dateien nie. Den Link prüft der Controller.
 */
class CloudLinkUploadRequest extends FormRequest
{
    /**
     * Nur gültige Upload-Links – geprüft vor der Datei, damit abgelaufene Links 404 liefern statt Validierungsfehlern.
     */
    public function authorize(): bool
    {
        /** @var CloudLink $link */
        $link = $this->route('link');

        return $link->type->allowsUpload() && app(CheckCloudLinkAccessAction::class)->execute($link);
    }

    protected function failedAuthorization(): never
    {
        throw new NotFoundHttpException;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['uploader_name' => trim((string) $this->input('uploader_name')) ?: null]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var CloudLink $link */
        $link = $this->route('link');

        return [
            'file' => [
                'required', 'file', 'max:'.$this->maxKilobytes(), new NotExecutableFileRule,
                ...($link->allow_all_types ? [] : ['mimetypes:'.implode(',', (array) config('band-manager.cloud.link_upload_types'))]),
            ],
            'uploader_name' => ['nullable', 'string', 'max:150'],
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
            'file.mimetypes' => 'Hier können nur Fotos und Videos hochgeladen werden.',
            'uploader_name.max' => 'Der Name ist zu lang (max. :max Zeichen).',
        ];
    }

    private function maxKilobytes(): int
    {
        return app(Images::class)->maxUploadKilobytes((int) config('band-manager.cloud.max_file_mb'));
    }
}
