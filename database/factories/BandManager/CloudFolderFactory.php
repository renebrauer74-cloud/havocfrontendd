<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\CloudFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Eigener Ordner in der obersten Ebene – Unterordner über ->inFolder($parent).
 *
 * @extends Factory<CloudFolder>
 */
class CloudFolderFactory extends Factory
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
            'parent_id' => null,
            'name' => fake()->unique()->words(2, true),
            'is_preset' => false,
        ];
    }

    public function inFolder(CloudFolder $parent): static
    {
        return $this->state(fn (): array => ['band_id' => $parent->band_id, 'parent_id' => $parent->id]);
    }

    /**
     * Vorgegebener Hauptordner (gesperrt).
     */
    public function preset(): static
    {
        return $this->state(fn (): array => ['is_preset' => true]);
    }
}
