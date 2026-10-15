<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Band;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Band>
 */
class BandFactory extends Factory
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
            'genre' => fake()->randomElement(['Metal', 'Punk', 'Rock', 'Hardcore']),
            'description' => fake()->sentence(),
            'contact_email' => fake()->safeEmail(),
        ];
    }
}
