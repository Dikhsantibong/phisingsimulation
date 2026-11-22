<?php

namespace Tests\Feature;

use App\Enums\RespondentStatus;
use App\Enums\SendChannel;
use App\Jobs\SendSimulationEmail;
use App\Jobs\SendSimulationWhatsApp;
use App\Mail\SimulationPhishingMail;
use App\Models\Respondent;
use App\Models\SimulationCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_research_key_creates_respondents_and_sends_email(): void
    {
        Mail::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();
        $campaign = SimulationCampaign::factory()->targeted()->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'campaign_id' => $campaign->id,
            'respondents' => [
                ['name' => 'Budi', 'class_group' => 'XII IPA 1', 'channel' => 'email', 'email' => 'budi@example.com', 'whatsapp_number' => '628123'],
            ],
        ])->assertRedirect(route('respondents.index'));

        $respondent = Respondent::where('email', 'budi@example.com')->first();
        $this->assertNotNull($respondent);
        $this->assertSame(RespondentStatus::Sent, $respondent->fresh()->status);
        Mail::assertSent(SimulationPhishingMail::class);
    }

    public function test_assigns_a_theme_to_every_created_respondent(): void
    {
        Mail::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();
        $campaign = SimulationCampaign::factory()->targeted()->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'campaign_id' => $campaign->id,
            'respondents' => [
                ['channel' => 'email', 'email' => 'a@example.com'],
                ['channel' => 'email', 'email' => 'b@example.com'],
            ],
        ])->assertRedirect(route('respondents.index'));

        $respondents = Respondent::whereIn('email', ['a@example.com', 'b@example.com'])->get();
        $this->assertCount(2, $respondents);
        $respondents->each(fn (Respondent $r) => $this->assertSame($campaign->theme, $r->theme));
    }

    public function test_a_link_mode_campaign_cannot_be_used_to_send_targeted_simulations(): void
    {
        Mail::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();
        $campaign = SimulationCampaign::factory()->link()->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'campaign_id' => $campaign->id,
            'respondents' => [
                ['channel' => 'email', 'email' => 'budi@example.com'],
            ],
        ])->assertSessionHasErrors('campaign_id');

        $this->assertSame(0, Respondent::count());
        Mail::assertNothingSent();
    }

    public function test_whatsapp_channel_dispatches_the_whatsapp_job_instead_of_email(): void
    {
        Queue::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();
        $campaign = SimulationCampaign::factory()->targeted()->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'campaign_id' => $campaign->id,
            'respondents' => [
                ['name' => 'Budi', 'channel' => 'whatsapp', 'whatsapp_number' => '628123456789'],
            ],
        ])->assertRedirect(route('respondents.index'));

        $respondent = Respondent::where('whatsapp_number', '628123456789')->first();
        $this->assertNotNull($respondent);
        $this->assertSame(SendChannel::WhatsApp, $respondent->send_channel);
        Queue::assertPushed(SendSimulationWhatsApp::class);
        Queue::assertNotPushed(SendSimulationEmail::class);
    }

    public function test_email_channel_requires_an_email_address(): void
    {
        $user = User::factory()->withResearchKey('kunci-riset')->create();
        $campaign = SimulationCampaign::factory()->targeted()->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'campaign_id' => $campaign->id,
            'respondents' => [
                ['channel' => 'email'],
            ],
        ])->assertSessionHasErrors('respondents.0.email');

        $this->assertSame(0, Respondent::count());
    }

    public function test_whatsapp_channel_requires_a_whatsapp_number(): void
    {
        $user = User::factory()->withResearchKey('kunci-riset')->create();
        $campaign = SimulationCampaign::factory()->targeted()->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'campaign_id' => $campaign->id,
            'respondents' => [
                ['channel' => 'whatsapp'],
            ],
        ])->assertSessionHasErrors('respondents.0.whatsapp_number');

        $this->assertSame(0, Respondent::count());
    }

    public function test_invalid_research_key_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'salah',
            'respondents' => [
                ['class_group' => 'XII IPA 1', 'email' => 'budi@example.com'],
            ],
        ])->assertSessionHasErrors('research_key');

        $this->assertSame(0, Respondent::count());
        Mail::assertNothingSent();
    }

    public function test_send_requires_a_configured_research_key(): void
    {
        $user = User::factory()->create(); // no research key set

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'apapun',
            'respondents' => [
                ['class_group' => 'XII IPA 1', 'email' => 'budi@example.com'],
            ],
        ])->assertSessionHasErrors('research_key');
    }

    public function test_research_key_can_be_set(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('research-key.update'), [
            'research_key' => 'kunci-baru',
            'research_key_confirmation' => 'kunci-baru',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('kunci-baru', $user->fresh()->research_key_hash));
    }

    public function test_guests_cannot_send_simulations(): void
    {
        $this->post(route('send-simulation.store'), [])->assertRedirect(route('login'));
    }

    /**
     * Re-sending to a WhatsApp number/email that already belongs to an
     * existing respondent is allowed on purpose (e.g. re-testing during the
     * study) — it creates a fresh respondent with its own session_token
     * rather than being silently blocked.
     */
    public function test_resending_to_an_already_registered_contact_is_allowed(): void
    {
        Queue::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();
        $campaign = SimulationCampaign::factory()->targeted()->create();
        $existing = Respondent::factory()->create(['whatsapp_number' => '628123456789']);

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'campaign_id' => $campaign->id,
            'respondents' => [
                ['channel' => 'whatsapp', 'whatsapp_number' => '628123456789'],
            ],
        ])->assertRedirect(route('respondents.index'));

        $respondents = Respondent::where('whatsapp_number', '628123456789')->get();
        $this->assertCount(2, $respondents);
        $this->assertNotSame($existing->session_token, $respondents->last()->session_token);
    }

    /**
     * The same contact repeated twice within one submitted batch (e.g. a
     * duplicate line in an imported CSV) must only create one respondent,
     * not two, for what is really a single row.
     */
    public function test_a_duplicate_contact_within_the_same_batch_is_only_created_once(): void
    {
        Queue::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();
        $campaign = SimulationCampaign::factory()->targeted()->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'campaign_id' => $campaign->id,
            'respondents' => [
                ['name' => 'Budi', 'channel' => 'whatsapp', 'whatsapp_number' => '628123456789'],
                ['name' => 'Budi (duplikat)', 'channel' => 'whatsapp', 'whatsapp_number' => '628123456789'],
            ],
        ])->assertRedirect(route('respondents.index'));

        $this->assertSame(1, Respondent::where('whatsapp_number', '628123456789')->count());
    }
}
