<?php

namespace Tests\Feature;

use App\Models\Respondent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_the_form_records_the_submit_action(): void
    {
        $respondent = Respondent::factory()->create();

        $this->post(route('simulation.behavior', ['respondent' => $respondent->session_token]), [
            'action' => 'submit',
            'keystroke_detected' => true,
        ])->assertRedirect();

        $this->assertSame('submit', $respondent->simulationEvent->fresh()->last_action);
    }

    public function test_declining_on_the_portal_records_the_reject_action(): void
    {
        $respondent = Respondent::factory()->create();

        $this->post(route('simulation.behavior', ['respondent' => $respondent->session_token]), [
            'action' => 'reject',
            'keystroke_detected' => false,
        ])->assertRedirect();

        $this->assertSame('reject', $respondent->simulationEvent->fresh()->last_action);
    }

    public function test_declining_from_the_email_link_records_the_report_action(): void
    {
        $respondent = Respondent::factory()->create();

        $this->get(route('simulation.access', ['respondent' => $respondent->session_token, 'action' => 'reject']))
            ->assertRedirect();

        $this->assertSame('report', $respondent->simulationEvent->fresh()->last_action);
    }
}
