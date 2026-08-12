<?php

namespace Database\Factories;

use App\Enums\BehaviorStatus;
use App\Enums\DeviceType;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationEvent>
 */
class SimulationEventFactory extends Factory
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
            'sent_at' => now()->subHours(2),
            'first_access_at' => null,
            'response_at' => null,
            'behavior_status' => BehaviorStatus::TidakMerespons,
            'keystroke_detected' => false,
            'device_type' => fake()->randomElement(DeviceType::cases()),
            'os_name' => fake()->randomElement(['Windows', 'Android', 'iOS', 'macOS']),
            'browser_name' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }

    public function risky(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_access_at' => now()->subHour(),
            'response_at' => now()->subMinutes(59),
            'behavior_status' => BehaviorStatus::Berisiko,
            'keystroke_detected' => true,
        ]);
    }

    public function alert(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_access_at' => now()->subHour(),
            'response_at' => now()->subMinutes(59),
            'behavior_status' => BehaviorStatus::Waspada,
            'keystroke_detected' => false,
        ]);
    }
}
