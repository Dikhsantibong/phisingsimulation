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

    public function test_it_schedules_a_reminder_for_a_portal_opened_but_abandoned(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);

        $respondent = Respondent::factory()->create(['status' => RespondentStatus::Clicked]);
        SimulationEvent::factory()->for($respondent)->create([
            'first_access_at' => now()->subHours(30),
            'response_at' => null,
        ]);

        $created = app(ReminderScheduler::class)->run();

        $this->assertSame(1, $created);
        $this->assertSame(
            1,
            $respondent->reminderLogs()->where('reminder_type', ReminderType::PortalDiabaikan)->count(),
        );
    }

    public function test_a_portal_abandoned_reminder_is_not_scheduled_before_the_threshold(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);

        $respondent = Respondent::factory()->create(['status' => RespondentStatus::Clicked]);
        SimulationEvent::factory()->for($respondent)->create([
            'first_access_at' => now()->subHour(),
            'response_at' => null,
        ]);

        $this->assertSame(0, app(ReminderScheduler::class)->run());
    }

    public function test_a_portal_abandoned_reminder_is_only_notified_once(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);

        $respondent = Respondent::factory()->create(['status' => RespondentStatus::Clicked]);
        SimulationEvent::factory()->for($respondent)->create([
            'first_access_at' => now()->subHours(30),
            'response_at' => null,
        ]);

        $scheduler = app(ReminderScheduler::class);
        $scheduler->run();
        $scheduler->run();

        $this->assertSame(1, $respondent->reminderLogs()->count());
    }

    /**
     * A per-respondent deadline (the "Batas Waktu Respon" set when sending)
     * must take priority over the global default threshold here too, same
     * as the other three reminder types — a respondent sent with a short
     * deadline shouldn't have to wait for the global default just because
     * they opened the portal but never finished it.
     */
    public function test_a_portal_abandoned_reminder_respects_the_respondents_own_deadline(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);

        $respondent = Respondent::factory()->create([
            'status' => RespondentStatus::Clicked,
            'expires_at' => now()->subMinutes(5),
        ]);
        SimulationEvent::factory()->for($respondent)->create([
            'first_access_at' => now()->subMinutes(10),
            'response_at' => null,
        ]);

        $created = app(ReminderScheduler::class)->run();

        $this->assertSame(1, $created);
        $this->assertSame(
            1,
            $respondent->reminderLogs()->where('reminder_type', ReminderType::PortalDiabaikan)->count(),
        );
    }

    public function test_a_portal_abandoned_reminder_waits_for_the_respondents_own_deadline_even_past_the_global_default(): void
    {
        config()->set('services.simulation.ignored_after_hours', 1);

        $respondent = Respondent::factory()->create([
            'status' => RespondentStatus::Clicked,
            'expires_at' => now()->addMinutes(5),
        ]);
        SimulationEvent::factory()->for($respondent)->create([
            'first_access_at' => now()->subHours(30),
            'response_at' => null,
        ]);

        $this->assertSame(0, app(ReminderScheduler::class)->run());
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

    /**
     * A respondent who genuinely redoes the simulation (e.g. the researcher
     * re-tests them, bumping response_at forward) deserves a fresh
     * reminder cycle — a stale log from a previous attempt must not block it.
     */
    public function test_a_new_simulation_attempt_gets_its_own_reminder_even_if_an_old_one_exists(): void
    {
        config()->set('services.simulation.questionnaire_after_hours', 24);

        $respondent = Respondent::factory()->create(['status' => RespondentStatus::CompletedBehavior]);
        $event = SimulationEvent::factory()->for($respondent)->create([
            'response_at' => now()->subHours(30),
        ]);

        // A stale reminder from a much earlier attempt.
        $staleReminder = $respondent->reminderLogs()->create([
            'reminder_type' => ReminderType::KuesionerBelumSelesai,
            'channel' => 'whatsapp',
            'scheduled_at' => now()->subDays(3),
            'attempt_number' => 1,
        ]);
        $staleReminder->forceFill(['created_at' => now()->subDays(3)])->save();

        // The respondent redid the simulation: a fresh response_at.
        $event->update(['response_at' => now()->subHours(30)]);

        $created = app(ReminderScheduler::class)->run();

        $this->assertSame(1, $created);
        $this->assertSame(
            2,
            $respondent->reminderLogs()->where('reminder_type', ReminderType::KuesionerBelumSelesai)->count(),
        );
    }

    /**
     * Deleting a reminder from the Pengingat page is a permanent dismissal:
     * the next scan must not silently recreate it (and re-notify the
     * researcher) just because the underlying condition still holds.
     */
    public function test_a_manually_deleted_reminder_is_not_recreated(): void
    {
        config()->set('services.simulation.ignored_after_hours', 24);

        $respondent = Respondent::factory()->sent()->create();
        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subHours(30),
            'first_access_at' => null,
        ]);

        app(ReminderScheduler::class)->run();
        $this->assertSame(1, $respondent->reminderLogs()->count());

        $respondent->reminderLogs()->first()->delete();
        $this->assertSame(0, $respondent->reminderLogs()->count());

        app(ReminderScheduler::class)->run();

        $this->assertSame(0, $respondent->reminderLogs()->count());
        $this->assertSame(1, $respondent->reminderLogs()->withTrashed()->count());
    }
}
