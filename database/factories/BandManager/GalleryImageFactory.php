<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Nur der Datensatz – Dateien legen die Tests bei Bedarf selbst auf dem Fake-Disk an.
 *
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = bin2hex(random_bytes(16));

        return [
            'band_id' => Band::factory(),
            'category' => 'band',
            'caption' => fake()->sentence(3),
            'credit' => fake()->name(),
            'is_main' => false,
            'sort_order' => 1,
            'file' => $name.'.jpg',
            'preview' => $name.'_p.jpg',
            'original_name' => 'bandfoto.jpg',
            'mime' => 'image/jpeg',
            'width' => 3000,
            'height' => 2000,
            'size_bytes' => 2_500_000,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (): array => ['is_main' => true, 'category' => GalleryImage::MAIN_CATEGORY]);
    }
}
