<?php

namespace Tests\Feature;

use App\Enums\ReminderType;
use App\Enums\RespondentStatus;
use App\Models\ReminderLog;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_single_reminder_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $reminder = ReminderLog::factory()->create();

        $this->actingAs($user)
            ->delete(route('reminders.destroy', $reminder))
            ->assertRedirect();

        $this->assertSoftDeleted('reminder_logs', ['id' => $reminder->id]);
    }

    public function test_several_reminders_can_be_deleted_at_once(): void
    {
        $user = User::factory()->create();
        $toDelete = ReminderLog::factory()->count(3)->create();
        $toKeep = ReminderLog::factory()->create();

        $this->actingAs($user)
            ->delete(route('reminders.bulk-destroy'), [
                'ids' => $toDelete->pluck('id')->all(),
            ])
            ->assertRedirect();

        foreach ($toDelete as $reminder) {
            $this->assertSoftDeleted('reminder_logs', ['id' => $reminder->id]);
        }

        $this->assertDatabaseHas('reminder_logs', ['id' => $toKeep->id]);
    }

    public function test_bulk_destroy_requires_at_least_one_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('reminders.bulk-destroy'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_deleting_a_reminder_does_not_touch_the_respondent(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create();
        $reminder = ReminderLog::factory()->for($respondent)->create([
            'reminder_type' => ReminderType::SimulasiDiabaikan,
        ]);

        $this->actingAs($user)->delete(route('reminders.destroy', $reminder));

        $this->assertDatabaseHas('respondents', ['id' => $respondent->id]);
    }

    public function test_deleting_reminders_requires_authentication(): void
    {
        $reminder = ReminderLog::factory()->create();

        $this->delete(route('reminders.destroy', $reminder))
            ->assertRedirect(route('login'));
    }

    /**
     * A "Belum Akses Simulasi" reminder is a snapshot of a moment that has
     * passed: if the respondent went on to actually open the link
     * afterwards, that old reminder must stop showing (and must not keep
     * misrepresenting them as having never accessed the simulation).
     */
    public function test_an_ignored_simulation_reminder_disappears_once_the_respondent_accesses_it(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['status' => RespondentStatus::Clicked]);
        SimulationEvent::factory()->for($respondent)->create([
            'first_access_at' => now(),
        ]);
        ReminderLog::factory()->for($respondent)->create([
            'reminder_type' => ReminderType::SimulasiDiabaikan,
        ]);

        $response = $this->actingAs($user)->get(route('reminders.index'));

        $response->assertInertia(fn (Assert $page) => $page->where('reminders.data', []));
    }

    public function test_an_ignored_simulation_reminder_still_shows_while_truly_unaccessed(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->sent()->create();
        SimulationEvent::factory()->for($respondent)->create([
            'first_access_at' => null,
        ]);
        ReminderLog::factory()->for($respondent)->create([
            'reminder_type' => ReminderType::SimulasiDiabaikan,
        ]);

        $response = $this->actingAs($user)->get(route('reminders.index'));

        $response->assertInertia(fn (Assert $page) => $page->where('reminders.data.0.respondent', $respondent->name ?? $respondent->email));
    }
}
