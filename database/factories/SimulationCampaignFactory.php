<?php

namespace Database\Factories;

use App\Enums\CampaignDistributionMode;
use App\Enums\SimulationTheme;
use App\Models\SimulationCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SimulationCampaign>
 */
class SimulationCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'title' => $title,
            'theme' => SimulationTheme::Elearning,
            'themes' => [SimulationTheme::Elearning->value],
            'distribution_mode' => CampaignDistributionMode::Targeted,
            'is_active' => true,
        ];
    }

    public function link(): static
    {
        return $this->state(fn (array $attributes) => [
            'distribution_mode' => CampaignDistributionMode::Link,
        ]);
    }

    public function targeted(): static
    {
        return $this->state(fn (array $attributes) => [
            'distribution_mode' => CampaignDistributionMode::Targeted,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Multiple themes configured, so each respondent gets a randomly assigned one.
     */
    public function multiTheme(): static
    {
        return $this->state(fn (array $attributes) => [
            'theme' => SimulationTheme::Elearning,
            'themes' => [SimulationTheme::Elearning->value, SimulationTheme::Tka->value, SimulationTheme::PengumumanNilaiTugas->value],
        ]);
    }
}
