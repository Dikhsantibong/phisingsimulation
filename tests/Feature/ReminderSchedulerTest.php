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

    public function test_an_ignored_simulation_is_only_notified_once(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);

        $respondent = Respondent::factory()->sent()->create();
        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subHours(30),
            'first_access_at' => null,
        ]);

        $scheduler = app(ReminderScheduler::class);
        $scheduler->run();
        $scheduler->run();
        $scheduler->run();

        $this->assertSame(1, $respondent->reminderLogs()->count());
    }

    public function test_it_schedules_an_early_notice_for_an_unfinished_questionnaire(): void
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

    public function test_the_early_notice_is_only_sent_once(): void
    {
        config()->set('services.simulation.questionnaire_after_hours', 24);

        $respondent = Respondent::factory()->create(['status' => RespondentStatus::CompletedBehavior]);
        SimulationEvent::factory()->for($respondent)->create([
            'response_at' => now()->subHours(30),
        ]);

        $scheduler = app(ReminderScheduler::class);
        $scheduler->run();
        $scheduler->run();

        $this->assertSame(
            1,
            $respondent->reminderLogs()->where('reminder_type', ReminderType::KuesionerBelumSelesai)->count(),
        );
    }

    public function test_it_schedules_a_final_notice_once_the_respondents_own_deadline_has_passed(): void
    {
        $respondent = Respondent::factory()->create([
            'status' => RespondentStatus::CompletedBehavior,
            'expires_at' => now()->subMinutes(5),
        ]);
        $respondent->forceFill(['created_at' => now()->subMinutes(10)])->save();

        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subMinutes(10),
            'response_at' => now()->subMinutes(9),
        ]);

        $created = app(ReminderScheduler::class)->run();

        // Both the early notice and the final notice fire together here,
        // since the respondent's own 5-minute deadline already elapsed
        // before the early-notice buffer even applied.
        $this->assertSame(2, $created);
        $this->assertSame(
            1,
            $respondent->reminderLogs()->where('reminder_type', ReminderType::BatasWaktuTerlewat)->count(),
        );
    }

    public function test_no_final_notice_is_scheduled_without_an_original_deadline(): void
    {
        config()->set('services.simulation.questionnaire_after_hours', 24);

        $respondent = Respondent::factory()->create([
            'status' => RespondentStatus::CompletedBehavior,
            'expires_at' => null,
        ]);
        SimulationEvent::factory()->for($respondent)->create([
            'response_at' => now()->subHours(30),
        ]);

        app(ReminderScheduler::class)->run();

        $this->assertSame(
            0,
            $respondent->reminderLogs()->where('reminder_type', ReminderType::BatasWaktuTerlewat)->count(),
        );
    }
}
