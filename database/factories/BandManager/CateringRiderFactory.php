<?php

namespace Database\Factories\BandManager;

use App\Enums\BandManager\CateringBuyoutEnum;
use App\Enums\BandManager\CateringMealEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\CateringRider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CateringRider>
 */
class CateringRiderFactory extends Factory
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
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_email' => fake()->safeEmail(),
            'people' => [
                ['name' => fake()->firstName(), 'role' => 'Gesang', 'diet' => 'vegan', 'allergy' => 'Gluten'],
                ['name' => fake()->firstName(), 'role' => 'Gitarre', 'diet' => '', 'allergy' => ''],
            ],
            'meals' => [
                CateringMealEnum::Dinner->value => ['wanted' => true, 'time' => '2 Std. vor Showbeginn', 'wishes' => 'Warmes Essen'],
            ],
            'buyout' => CateringBuyoutEnum::Catering,
            'drinks' => [
                ['item' => 'Stilles Wasser 0,5 l', 'amount' => '24 Flaschen', 'note' => ''],
            ],
            'dressing_rooms' => 1,
            'stage_towels' => 4,
            'custom_sections' => [],
        ];
    }

    /**
     * Buyout statt Essen.
     */
    public function buyout(float $amount = 25): static
    {
        return $this->state(fn (): array => ['buyout' => CateringBuyoutEnum::Preferred, 'buyout_amount' => $amount]);
    }
}
