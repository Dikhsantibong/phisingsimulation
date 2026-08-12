<?php

namespace Database\Seeders;

use App\Enums\RespondentStatus;
use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->withResearchKey('rahasia-riset')->create([
            'name' => 'Peneliti',
            'email' => 'test@example.com',
        ]);

        // Respondents who never opened the email.
        Respondent::factory(3)
            ->sent()
            ->has(SimulationEvent::factory(), 'simulationEvent')
            ->create();

        // Respondents who clicked and submitted the fake form (risky behaviour).
        Respondent::factory(4)
            ->state(['status' => RespondentStatus::CompletedQuestionnaire])
            ->has(SimulationEvent::factory()->risky(), 'simulationEvent')
            ->has(QuestionnaireResult::factory(), 'questionnaireResult')
            ->create();

        // Respondents who reported the phishing attempt (alert behaviour).
        Respondent::factory(3)
            ->state(['status' => RespondentStatus::Finished])
            ->has(SimulationEvent::factory()->alert(), 'simulationEvent')
            ->has(QuestionnaireResult::factory(), 'questionnaireResult')
            ->create();
    }
}
