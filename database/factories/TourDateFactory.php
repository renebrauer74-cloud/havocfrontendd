<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\TourDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TourDate>
 */
class TourDateFactory extends Factory
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
            'date' => fake()->dateTimeBetween('now', '+1 year')->format('d.m.Y'),
            'city' => fake()->city(),
            'venue' => fake()->company(),
            'link' => fake()->url(),
        ];
    }
}
