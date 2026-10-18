<?php

namespace Database\Factories\BandManager;

use App\Enums\BandManager\CloudLinkTypeEnum;
use App\Models\BandManager\CloudFile;
use App\Models\BandManager\CloudFolder;
use App\Models\BandManager\CloudLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Ziel über ->forFolder($folder) bzw. ->forFile($file).
 *
 * @extends Factory<CloudLink>
 */
class CloudLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(40),
            'type' => CloudLinkTypeEnum::Read,
            'expires_at' => now()->addDays(30),
        ];
    }

    public function forFolder(CloudFolder $folder): static
    {
        return $this->state(fn (): array => ['band_id' => $folder->band_id, 'folder_id' => $folder->id]);
    }

    public function forFile(CloudFile $file): static
    {
        return $this->state(fn (): array => ['band_id' => $file->band_id, 'file_id' => $file->id]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }
}
