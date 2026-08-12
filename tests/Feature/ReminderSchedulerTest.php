<?php

namespace Tests\Feature;

use App\Enums\ReminderType;
use App\Enums\RespondentStatus;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use App\Services\ReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_schedules_a_reminder_for_an_ignored_simulation(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);

        $respondent = Respondent::factory()->sent()->create();
        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subHours(30),
            'first_access_at' => null,
        ]);

        $created = app(ReminderScheduler::class)->run();

        $this->assertSame(1, $created);
        $this->assertSame(1, $respondent->reminderLogs()->where('reminder_type', ReminderType::SimulasiDiabaikan)->count());
    }

    public function test_it_does_not_schedule_before_the_threshold(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);

        $respondent = Respondent::factory()->sent()->create();
        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subHour(),
            'first_access_at' => null,
        ]);

        $this->assertSame(0, app(ReminderScheduler::class)->run());
    }

    public function test_reminder_cap_is_respected(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);
        config()->set('services.simulation.max_reminders', 2);

        $respondent = Respondent::factory()->sent()->create();
        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subHours(30),
            'first_access_at' => null,
        ]);

        $scheduler = app(ReminderScheduler::class);
        $scheduler->run(); // attempt 1
        $scheduler->run(); // attempt 2
        $scheduler->run(); // capped, no new reminder

        $this->assertSame(2, $respondent->reminderLogs()->count());
    }

    public function test_it_schedules_a_reminder_for_an_unfinished_questionnaire(): void
    {
        config()->set('services.simulation.questionnaire_after_hours', 24);

        $respondent = Respondent::factory()->create(['status' => RespondentStatus::CompletedBehavior]);
        SimulationEvent::factory()->for($respondent)->create([
            'response_at' => now()->subHours(30),
        ]);

        $created = app(ReminderScheduler::class)->run();

        $this->assertSame(1, $created);
        $this->assertSame(
            1,
            $respondent->reminderLogs()->where('reminder_type', ReminderType::KuesionerBelumSelesai)->count(),
        );
    }
}
