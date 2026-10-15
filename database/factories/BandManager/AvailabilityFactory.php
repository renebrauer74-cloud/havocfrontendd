<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Availability;
use App\Models\BandManager\Band;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
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
            'date' => fake()->unique()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['green', 'orange', 'red']),
            'note' => null,
        ];
    }
}
