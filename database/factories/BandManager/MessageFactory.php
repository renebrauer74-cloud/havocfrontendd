<?php

namespace Database\Factories\BandManager;

use App\Models\BandManager\Message;
use App\Models\BandManager\MessageThread;
use App\Models\BandManager\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'thread_id' => MessageThread::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
