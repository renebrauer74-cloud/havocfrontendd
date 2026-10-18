<?php

namespace Database\Factories\BandManager;

use App\Enums\BandManager\BacklineProviderEnum;
use App\Enums\BandManager\MicStandEnum;
use App\Enums\BandManager\MonitorTypeEnum;
use App\Models\BandManager\Band;
use App\Models\BandManager\TechRider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechRider>
 */
class TechRiderFactory extends Factory
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
            'soundcheck_minutes' => 45,
            'show_minutes' => 60,
            'line_up' => [
                ['name' => fake()->firstName(), 'instrument' => 'Gesang'],
                ['name' => fake()->firstName(), 'instrument' => 'Gitarre'],
            ],
            'inputs' => [
                ['source' => 'Kick', 'mic' => 'Beta 91A', 'stand' => null, 'phantom' => true, 'note' => ''],
                ['source' => 'Lead Vocals', 'mic' => 'SM58', 'stand' => MicStandEnum::TallBoom->value, 'phantom' => false, 'note' => ''],
            ],
            'monitors' => [
                ['who' => 'Gesang', 'type' => MonitorTypeEnum::Wedge->value, 'note' => 'Viel Gesang'],
            ],
            'backline' => [
                ['item' => 'Drumset (Kick 22", Toms 12"/16")', 'provider' => BacklineProviderEnum::Venue->value, 'note' => ''],
            ],
            'custom_sections' => [],
            'pa_notes' => 'Professionelle PA, passend zur Raumgröße.',
        ];
    }
}
