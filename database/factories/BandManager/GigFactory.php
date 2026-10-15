<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\Gig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gig>
 */
class GigFactory extends Factory
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
            'date' => fake()->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'title' => fake()->randomElement(['Club Show', 'Festival', 'Release Party', 'Support Show']),
            'venue' => fake()->company(),
            'city' => fake()->city(),
            'fee' => fake()->randomFloat(2, 300, 5000),
            'status' => 'request',
            'booked_by' => Gig::BOOKED_BY_AGENCY,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => ['status' => Gig::STATUS_CONFIRMED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => Gig::STATUS_CANCELLED]);
    }

    public function bandOwned(): static
    {
        return $this->state(fn (): array => ['booked_by' => Gig::BOOKED_BY_BAND]);
    }

    public function past(): static
    {
        return $this->state(fn (): array => ['date' => fake()->dateTimeBetween('-6 months', '-1 day')->format('Y-m-d')]);
    }
}
