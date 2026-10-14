<?php

namespace Database\Factories;

use App\Models\AgencyShow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgencyShow>
 */
class AgencyShowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'date' => fake()->dateTimeBetween('now', '+1 year')->format('d.m.Y'),
            'city' => fake()->city(),
            'venue' => fake()->company(),
            'ticket_url' => fake()->url(),
            'info_text' => fake()->paragraph(),
        ];
    }
}
