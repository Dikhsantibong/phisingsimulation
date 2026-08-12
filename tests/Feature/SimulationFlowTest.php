<?php

namespace Tests\Feature;

use App\Enums\BehaviorStatus;
use App\Enums\RespondentStatus;
use App\Models\Respondent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_clicking_the_link_records_first_access_and_device_metadata(): void
    {
        $respondent = Respondent::factory()->sent()->create();

        $response = $this->get(
            route('simulation.access', ['respondent' => $respondent->session_token]),
            ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605 Safari/604'],
        );

        $response->assertOk();

        $event = $respondent->simulationEvent()->first();
        $this->assertNotNull($event->first_access_at);
        $this->assertSame('mobile', $event->device_type->value);
        $this->assertSame('iOS', $event->os_name);
        $this->assertSame('Safari', $event->browser_name);
        $this->assertSame(RespondentStatus::Clicked, $respondent->fresh()->status);
    }

    public function test_ip_address_is_stored_hashed_not_raw(): void
    {
        $respondent = Respondent::factory()->sent()->create();

        $this->get(route('simulation.access', ['respondent' => $respondent->session_token]));

        $event = $respondent->simulationEvent()->first();
        $this->assertNotNull($event->ip_hash);
        $this->assertNotSame('127.0.0.1', $event->ip_hash);
        $this->assertSame(64, strlen($event->ip_hash)); // sha256 hex length
    }

    public function test_submitting_the_fake_form_is_recorded_as_risky(): void
    {
        $respondent = Respondent::factory()->clicked()->create();

        $this->post(route('simulation.behavior', ['respondent' => $respondent->session_token]), [
            'action' => 'submit',
            'keystroke_detected' => false,
        ])->assertRedirect(route('simulation.reveal', ['respondent' => $respondent->session_token]));

        $event = $respondent->simulationEvent()->first();
        $this->assertSame(BehaviorStatus::Berisiko, $event->behavior_status);
        $this->assertTrue($event->keystroke_detected);
        $this->assertNotNull($event->response_at);
        $this->assertSame(RespondentStatus::CompletedBehavior, $respondent->fresh()->status);
    }

    public function test_reporting_without_typing_is_recorded_as_alert(): void
    {
        $respondent = Respondent::factory()->clicked()->create();

        $this->post(route('simulation.behavior', ['respondent' => $respondent->session_token]), [
            'action' => 'report',
            'keystroke_detected' => false,
        ]);

        $this->assertSame(BehaviorStatus::Waspada, $respondent->simulationEvent()->first()->behavior_status);
    }

    public function test_reporting_after_typing_is_recorded_as_neutral(): void
    {
        $respondent = Respondent::factory()->clicked()->create();

        $this->post(route('simulation.behavior', ['respondent' => $respondent->session_token]), [
            'action' => 'report',
            'keystroke_detected' => true,
        ]);

        $this->assertSame(BehaviorStatus::Netral, $respondent->simulationEvent()->first()->behavior_status);
    }

    public function test_behavior_endpoint_rejects_unknown_actions(): void
    {
        $respondent = Respondent::factory()->clicked()->create();

        $this->post(route('simulation.behavior', ['respondent' => $respondent->session_token]), [
            'action' => 'steal-password',
            'keystroke_detected' => true,
        ])->assertSessionHasErrors('action');
    }

    public function test_reveal_page_is_shown_before_the_questionnaire(): void
    {
        config()->set('services.simulation.tally_url', 'https://tally.so/r/abc123');
        $respondent = Respondent::factory()->create();

        $this->get(route('simulation.reveal', ['respondent' => $respondent->session_token]))
            ->assertOk();
    }
}
