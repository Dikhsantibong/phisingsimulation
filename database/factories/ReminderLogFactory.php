<?php

namespace Database\Factories;

use App\Enums\ReminderChannel;
use App\Enums\ReminderType;
use App\Models\ReminderLog;
use App\Models\Respondent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReminderLog>
 */
class ReminderLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'respondent_id' => Respondent::factory(),
            'reminder_type' => fake()->randomElement(ReminderType::cases()),
            'channel' => ReminderChannel::WhatsApp,
            'scheduled_at' => now()->addHours(24),
            'sent_at' => null,
            'followed_up_at' => null,
            'attempt_number' => 1,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'sent_at' => now(),
        ]);
    }
}
