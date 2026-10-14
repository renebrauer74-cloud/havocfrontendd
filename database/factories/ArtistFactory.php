<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artist>
 */
class ArtistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'genre' => fake()->randomElement(['Post-Hardcore', 'Metalcore', 'Punk', 'Alternative Rock']),
            'bio' => fake()->paragraph(),
            'bio_en' => fake()->paragraph(),
            'spotify_url' => 'https://open.spotify.com/artist/'.fake()->lexify('??????????'),
            'youtube_url' => '',
            'website_url' => fake()->url(),
            'instagram_url' => '',
            'tiktok_url' => '',
        ];
    }
}
