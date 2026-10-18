<?php

namespace Database\Factories;

use App\Enums\MediaCollectionEnum;
use App\Models\Artist;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
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
            'artist_id' => Artist::factory(),
        ];
    }

    /**
     * Mit hochgeladenem Bild (Media Library).
     */
    public function withImage(): static
    {
        return $this->afterCreating(function (GalleryImage $image): void {
            $image->addMedia(UploadedFile::fake()->image('foto.jpg'))->toMediaCollection(MediaCollectionEnum::Gallery->value);
        });
    }
}
