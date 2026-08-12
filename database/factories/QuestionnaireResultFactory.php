<?php

namespace Database\Factories;

use App\Enums\CompletionStatus;
use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuestionnaireResult>
 */
class QuestionnaireResultFactory extends Factory
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
            'tally_submission_id' => (string) Str::uuid(),
            'completion_status' => CompletionStatus::Selesai,
            'knowledge_answers' => ['k1' => fake()->numberBetween(1, 5), 'k2' => fake()->numberBetween(1, 5)],
            'attitude_answers' => ['a1' => fake()->numberBetween(1, 5), 'a2' => fake()->numberBetween(1, 5)],
            'behavior_answers' => ['b1' => fake()->numberBetween(1, 5), 'b2' => fake()->numberBetween(1, 5)],
            'submitted_at' => now(),
        ];
    }
}
