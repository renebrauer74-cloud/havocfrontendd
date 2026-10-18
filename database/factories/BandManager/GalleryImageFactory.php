<?php

namespace Database\Factories\BandManager;

use App\Enums\BandManager\GalleryCategoryEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * Nur der Datensatz – das Foto über ->withPhoto().
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
        return [
            'band_id' => Band::factory(),
            'category' => 'band',
            'caption' => fake()->sentence(3),
            'credit' => fake()->name(),
            'is_main' => false,
            'sort_order' => 1,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (): array => ['is_main' => true, 'category' => GalleryCategoryEnum::Band]);
    }

    /**
     * Mit Foto (inkl. Vorschau-Conversion) in der Media Library.
     */
    public function withPhoto(int $width = 2000, int $height = 1000): static
    {
        return $this->afterCreating(function (GalleryImage $image) use ($width, $height): void {
            $image->addMedia(UploadedFile::fake()->image('bandfoto.jpg', $width, $height))
                ->withCustomProperties(['width' => $width, 'height' => $height])
                ->toMediaCollection(MediaCollectionEnum::BandPhotos->value);
        });
    }
}
