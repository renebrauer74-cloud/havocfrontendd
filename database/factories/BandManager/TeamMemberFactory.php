<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
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
            'type' => TeamMember::TYPE_MEMBER,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'position' => fake()->randomElement(['Gitarre', 'Gesang', 'Bass', 'Schlagzeug']),
        ];
    }

    public function crew(string $role = 'sound'): static
    {
        return $this->state(fn (): array => ['type' => TeamMember::TYPE_CREW, 'crew_role' => $role, 'position' => null]);
    }
}
