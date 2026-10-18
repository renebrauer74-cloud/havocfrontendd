<?php

namespace App\Actions\BandManager\Cloud;

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\Support\File;

/**
 * Speichert eine hochgeladene Datei im Cloud-Speicher, sofern noch genug Platz frei ist.
 * Die Band-Zeile wird dabei gesperrt, damit gleichzeitige Uploads das Limit nicht gemeinsam überschreiten.
 */
class StoreCloudFileAction
{
    public function __construct(private CalculateCloudUsageAction $calculateUsage) {}

    /**
     * @throws ValidationException wenn der Speicherplatz nicht reicht
     */
    public function execute(Band $band, ?CloudFolder $folder, UploadedFile $upload, ?User $uploader): CloudFile
    {
        return DB::transaction(function () use ($band, $folder, $upload, $uploader): CloudFile {
            Band::query()->whereKey($band->id)->lockForUpdate()->first();

            $usage = $this->calculateUsage->execute($band);

            if ((int) $upload->getSize() > $usage['free']) {
                throw ValidationException::withMessages([
                    'file' => '„'.$upload->getClientOriginalName().'“: Nicht genug Speicherplatz – noch '.File::getHumanReadableSize($usage['free'])
                        .' von '.File::getHumanReadableSize($usage['quota']).' frei. Bitte Dateien löschen oder den Papierkorb leeren.',
                ]);
            }

            $file = CloudFile::query()->create([
                'band_id' => $band->id,
                'folder_id' => $folder?->id,
                'uploaded_by' => $uploader?->id,
            ]);

            $file->addMedia($upload)
                ->withCustomProperties(['band_id' => $band->id, ...$this->dimensions($upload)])
                ->toMediaCollection(MediaCollectionEnum::CloudFiles->value);

            return $file;
        });
    }

    /**
     * Breite und Höhe für die Vorschau-Conversion (siehe CloudFile::registerMediaConversions()).
     *
     * @return array{width?: int, height?: int}
     */
    private function dimensions(UploadedFile $upload): array
    {
        if (! in_array($upload->getMimeType(), CloudFile::THUMBNAIL_TYPES, true)) {
            return [];
        }

        $imageSize = @getimagesize((string) $upload->getRealPath());

        return $imageSize === false ? [] : ['width' => $imageSize[0], 'height' => $imageSize[1]];
    }
}
