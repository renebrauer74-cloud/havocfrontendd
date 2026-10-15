<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Band;
use App\Models\BandManager\MessageThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageThread>
 */
class MessageThreadFactory extends Factory
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
            'subject' => fake()->sentence(4),
            'last_message_at' => now(),
        ];
    }
}
