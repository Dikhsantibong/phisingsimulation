<?php

namespace Database\Factories;

use App\Enums\RespondentStatus;
use App\Models\Respondent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Respondent>
 */
class RespondentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_group' => fake()->randomElement(['XII IPA 1', 'XII IPA 2', 'XII IPS 1', 'XI IPA 1']),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'whatsapp_number' => '628'.fake()->numerify('##########'),
            'status' => RespondentStatus::Pending,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RespondentStatus::Sent,
        ]);
    }

    public function clicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RespondentStatus::Clicked,
        ]);
    }
}
