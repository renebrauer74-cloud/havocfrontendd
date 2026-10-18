<?php

namespace Database\Factories\BandManager;

use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * Datensatz mit Datei in der Media Library (Größe über ->withFile()).
 *
 * @extends Factory<CloudFile>
 */
class CloudFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'band_id' => Band::factory(),
            'folder_id' => null,
        ];
    }

    public function inFolder(CloudFolder $folder): static
    {
        return $this->state(fn (): array => ['band_id' => $folder->band_id, 'folder_id' => $folder->id]);
    }

    /**
     * Datei mit Name und echter Größe in Kilobyte (die Media Library misst die Datei selbst).
     */
    public function withFile(string $name = 'notizen.txt', int $kilobytes = 10): static
    {
        return $this->afterCreating(function (CloudFile $file) use ($name, $kilobytes): void {
            $file->addMedia(UploadedFile::fake()->createWithContent($name, str_repeat('x', $kilobytes * 1024)))
                ->withCustomProperties(['band_id' => $file->band_id])
                ->toMediaCollection(MediaCollectionEnum::CloudFiles->value);
        });
    }
}
