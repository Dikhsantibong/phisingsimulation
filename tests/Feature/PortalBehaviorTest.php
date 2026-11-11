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

    public function test_reporting_as_suspicious_on_the_portal_records_the_report_action(): void
    {
        $respondent = Respondent::factory()->create();

        $this->post(route('simulation.behavior', ['respondent' => $respondent->session_token]), [
            'action' => 'report',
            'keystroke_detected' => false,
        ])->assertRedirect();

        $this->assertSame('report', $respondent->simulationEvent->fresh()->last_action);
    }

    /**
     * Declining straight from the email/WA link (never opening the portal at
     * all) must record a different action than the portal's own "Laporkan
     * sebagai Mencurigakan" button — otherwise the two very different
     * behaviors (never touched the portal vs. opened it then reported it)
     * collapse into the same, misleading export label.
     */
    public function test_declining_from_the_email_link_records_a_distinct_reject_email_action(): void
    {
        $respondent = Respondent::factory()->create();

        $this->get(route('simulation.access', ['respondent' => $respondent->session_token, 'action' => 'reject']))
            ->assertRedirect();

        $this->assertSame('reject_email', $respondent->simulationEvent->fresh()->last_action);
    }
}
