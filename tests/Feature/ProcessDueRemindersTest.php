<?php

namespace Tests\Feature;

use App\Models\Respondent;
use App\Models\SimulationEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProcessDueRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.simulation.auto_reminders_enabled', true);
        config()->set('services.simulation.reminder_run_interval_minutes', 1);
        config()->set('services.simulation.ignored_after_hours', 24);
    }

    public function test_web_traffic_triggers_the_reminder_scan(): void
    {
        $respondent = Respondent::factory()->sent()->create(['expires_at' => null]);
        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subHours(30),
            'first_access_at' => null,
        ]);

        // An ordinary page visit should run the scan (in terminate) and queue a reminder.
        $this->get('/')->assertOk();

        $this->assertSame(1, $respondent->reminderLogs()->count());
        $this->assertNotNull(Cache::get('simulation:reminders:last_run'));
    }

    public function test_scan_runs_at_most_once_per_interval(): void
    {
        $respondent = Respondent::factory()->sent()->create(['expires_at' => null]);
        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subHours(30),
            'first_access_at' => null,
        ]);

        $this->get('/')->assertOk();
        $this->get('/')->assertOk(); // within the same interval window

        // The cache guard prevents a second scan, so the cap logic isn't retriggered.
        $this->assertSame(1, $respondent->reminderLogs()->count());
    }

    public function test_disabling_the_flag_skips_the_scan(): void
    {
        config()->set('services.simulation.auto_reminders_enabled', false);

        $respondent = Respondent::factory()->sent()->create(['expires_at' => null]);
        SimulationEvent::factory()->for($respondent)->create([
            'sent_at' => now()->subHours(30),
            'first_access_at' => null,
        ]);

        $this->get('/')->assertOk();

        $this->assertSame(0, $respondent->reminderLogs()->count());
    }
}
